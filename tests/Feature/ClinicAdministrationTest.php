<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ClinicAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        $this->tenant = Tenant::query()->create(['code' => 'ADM-001', 'name' => 'کلینیک مدیریت', 'status' => 'active', 'plan_code' => 'free']);
        $this->manager = User::factory()->create(['username' => 'clinic_admin']);
        $role = Role::query()->where('code', 'clinic_manager')->firstOrFail();
        $this->tenant->users()->attach($this->manager->id, ['role_id' => $role->id, 'status' => 'active']);
    }

    public function test_manager_can_create_doctor_and_receptionist_records(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/users', [
                'name' => 'دکتر احمدی',
                'mobile' => '09121111111',
                'username' => 'doctor_ahmadi',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
                'role' => 'doctor',
                'license_no' => '۱۲۳۴۵',
                'specialty' => 'جراحی لثه',
            ]);

        $response->assertRedirect('/clinic/users');
        $doctor = User::query()->where('username', 'doctor_ahmadi')->firstOrFail();
        $this->assertDatabaseHas('practitioners', ['tenant_id' => $this->tenant->id, 'user_id' => $doctor->id, 'specialty' => 'جراحی لثه']);

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/users', [
                'name' => 'منشی کلینیک',
                'mobile' => '09122222222',
                'username' => 'receptionist_one',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
                'role' => 'receptionist',
            ])
            ->assertRedirect('/clinic/users');

        $receptionist = User::query()->where('username', 'receptionist_one')->firstOrFail();
        $this->assertDatabaseHas('clinic_staff', ['tenant_id' => $this->tenant->id, 'user_id' => $receptionist->id, 'staff_type' => 'receptionist']);
    }

    public function test_system_admin_can_change_default_font(): void
    {
        $admin = User::factory()->create(['username' => 'appearance_admin', 'is_system_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/settings/appearance', ['default_font' => 'Tahoma'])
            ->assertRedirect();

        $setting = DB::table('platform_settings')->where('key', 'default_font')->first();
        self::assertNotNull($setting);
        self::assertSame('Tahoma', json_decode((string) $setting->value, true, 512, JSON_THROW_ON_ERROR));
    }
}
