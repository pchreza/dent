<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SuperAdminTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_super_admin_can_create_tenant_and_initial_manager(): void
    {
        $admin = User::factory()->systemAdmin()->create([
            'mobile' => '09999999999',
            'username' => 'root_admin',
        ]);

        $response = $this->actingAs($admin)->post('/admin/tenants', [
            'name' => 'کلینیک نمونه',
            'code' => 'CLINIC-001',
            'plan_code' => 'free',
            'starts_on' => '2026-08-12',
            'ends_on' => '2026-09-12',
            'manager_name' => 'مدیر نمونه',
            'manager_mobile' => '۰۹۱۲۳۴۵۶۷۸۸',
            'manager_username' => 'clinic_manager_1',
            'manager_password' => 'correct horse battery staple',
            'manager_password_confirmation' => 'correct horse battery staple',
        ]);

        $response->assertRedirect('/admin/tenants');
        $tenant = Tenant::query()->where('code', 'CLINIC-001')->firstOrFail();
        $manager = User::query()->where('username', 'clinic_manager_1')->firstOrFail();

        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $tenant->id,
            'user_id' => $manager->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'action' => 'tenant.created',
            'tenant_id' => $tenant->id,
            'actor_id' => $admin->id,
        ]);
        self::assertTrue($manager->must_change_password);
    }

    public function test_non_system_admin_cannot_open_tenant_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/tenants')->assertForbidden();
    }
}
