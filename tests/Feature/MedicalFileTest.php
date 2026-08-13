<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FileAsset;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MedicalFileTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $manager;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        $this->tenant = Tenant::query()->create(['code' => 'FILE-001', 'name' => 'کلینیک فایل', 'status' => 'active', 'plan_code' => 'free']);
        $this->manager = User::factory()->create(['username' => 'file_manager']);
        $role = Role::query()->where('code', 'clinic_manager')->firstOrFail();
        $this->tenant->users()->attach($this->manager->id, ['role_id' => $role->id, 'status' => 'active']);
        $this->patient = $this->createPatient($this->tenant, 'سارا', 'پرونده', 'P-FILE-1', '09121111111');
    }

    public function test_manager_can_upload_private_medical_file_and_download_it(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post(route('patients.medical-files.store', ['patientId' => $this->patient->id]), [
                'file' => UploadedFile::fake()->image('radiology.jpg', 40, 40),
                'category' => 'xray',
                'title' => 'رادیوگرافی اولیه',
            ]);

        $response->assertRedirect(route('patients.show', ['patientId' => $this->patient->id]));
        $asset = FileAsset::query()->firstOrFail();
        self::assertSame(Patient::class, $asset->owner_type);
        self::assertSame('xray', $asset->category);
        self::assertSame('رادیوگرافی اولیه', $asset->title());
        self::assertStringStartsWith('medical/'.$this->tenant->id.'/'.$this->patient->id.'/', $asset->path);
        self::assertStringNotContainsString('radiology.jpg', $asset->path);
        Storage::disk('local')->assertExists($asset->path);
        $this->assertDatabaseHas('audit_events', ['action' => 'medical_file.uploaded', 'subject_id' => $asset->id]);

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('patients.show', ['patientId' => $this->patient->id]))
            ->assertOk()
            ->assertSee('فایل‌های پزشکی')
            ->assertSee('رادیوگرافی اولیه');

        $download = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('patients.medical-files.download', ['patientId' => $this->patient->id, 'fileId' => $asset->id]));

        $download->assertOk()->assertHeader('content-type', 'image/jpeg')->assertHeader('x-content-type-options', 'nosniff');
        $this->assertDatabaseHas('audit_events', ['action' => 'medical_file.downloaded', 'subject_id' => $asset->id]);
    }

    public function test_invalid_type_and_oversized_file_are_rejected_without_storage_record(): void
    {
        $invalid = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post(route('patients.medical-files.store', ['patientId' => $this->patient->id]), [
                'file' => UploadedFile::fake()->create('medical.pdf', 100, 'application/pdf'),
                'category' => 'other',
            ]);
        $invalid->assertRedirect()->assertSessionHasErrors('file');
        $this->assertDatabaseCount('file_assets', 0);

        $spoofed = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post(route('patients.medical-files.store', ['patientId' => $this->patient->id]), [
                'file' => UploadedFile::fake()->createWithContent('spoof.png', 'not a real image'),
                'category' => 'other',
            ]);
        $spoofed->assertRedirect()->assertSessionHasErrors('file');
        $this->assertDatabaseCount('file_assets', 0);

        $large = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post(route('patients.medical-files.store', ['patientId' => $this->patient->id]), [
                'file' => UploadedFile::fake()->create('large.jpg', 1200, 'image/jpeg'),
                'category' => 'other',
            ]);
        $large->assertRedirect()->assertSessionHasErrors('file');
        $this->assertDatabaseCount('file_assets', 0);
    }

    public function test_archiving_hides_file_and_blocks_future_download_but_keeps_private_object(): void
    {
        $this->uploadFixture();
        $asset = FileAsset::query()->firstOrFail();

        $archive = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->delete(route('patients.medical-files.archive', ['patientId' => $this->patient->id, 'fileId' => $asset->id]));

        $archive->assertRedirect(route('patients.show', ['patientId' => $this->patient->id]));
        $asset->refresh();
        self::assertNotNull($asset->deleted_at);
        Storage::disk('local')->assertExists($asset->path);
        $this->assertDatabaseHas('audit_events', ['action' => 'medical_file.archived', 'subject_id' => $asset->id]);

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('patients.show', ['patientId' => $this->patient->id]))
            ->assertOk()
            ->assertSee('فایل پزشکی ثبت نشده است.');

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('patients.medical-files.download', ['patientId' => $this->patient->id, 'fileId' => $asset->id]))
            ->assertNotFound();
    }

    public function test_file_from_another_tenant_cannot_be_downloaded_with_direct_identifier(): void
    {
        $otherTenant = Tenant::query()->create(['code' => 'FILE-002', 'name' => 'کلینیک دیگر', 'status' => 'active', 'plan_code' => 'free']);
        $otherPatient = $this->createPatient($otherTenant, 'رضا', 'دیگر', 'P-FILE-2', '09122222222');
        $path = 'medical/'.$otherTenant->id.'/'.$otherPatient->id.'/foreign.png';
        Storage::disk('local')->put($path, 'not-a-real-image');
        $asset = FileAsset::query()->create([
            'tenant_id' => $otherTenant->id,
            'owner_type' => Patient::class,
            'owner_id' => $otherPatient->id,
            'category' => 'other',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'image/png',
            'size' => 16,
            'metadata_json' => ['original_name' => 'foreign.png'],
        ]);

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('patients.medical-files.download', ['patientId' => $this->patient->id, 'fileId' => $asset->id]))
            ->assertNotFound();
    }

    public function test_user_without_create_permission_cannot_upload_by_direct_http(): void
    {
        $reader = Role::query()->create(['tenant_id' => $this->tenant->id, 'code' => 'file_reader', 'name' => 'مشاهده‌گر پرونده', 'is_system' => false]);
        $reader->permissions()->sync(Permission::query()->whereIn('code', ['patients.view', 'clinical_files.view'])->pluck('id')->mapWithKeys(
            static fn (int $id): array => [$id => ['allowed' => true]],
        )->all());
        DB::table('tenant_user')->where('tenant_id', $this->tenant->id)->where('user_id', $this->manager->id)->update(['role_id' => $reader->id]);

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post(route('patients.medical-files.store', ['patientId' => $this->patient->id]), [
                'file' => UploadedFile::fake()->image('blocked.png', 20, 20),
                'category' => 'other',
            ])
            ->assertForbidden();
        $this->assertDatabaseCount('file_assets', 0);
    }

    private function uploadFixture(): FileAsset
    {
        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post(route('patients.medical-files.store', ['patientId' => $this->patient->id]), [
                'file' => UploadedFile::fake()->image('scan.png', 32, 32),
                'category' => 'other',
            ])
            ->assertRedirect();

        return FileAsset::query()->firstOrFail();
    }

    private function createPatient(Tenant $tenant, string $firstName, string $lastName, string $patientNo, string $mobile): Patient
    {
        return Patient::query()->create([
            'tenant_id' => $tenant->id,
            'patient_no' => $patientNo,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'national_id' => $patientNo,
            'mobile' => $mobile,
            'status' => 'active',
        ]);
    }
}
