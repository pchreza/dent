<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private User $managerA;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(DatabaseSeeder::class);

        $managerRole = Role::query()->where('code', 'clinic_manager')->firstOrFail();
        $this->tenantA = Tenant::query()->create(['code' => 'A-001', 'name' => 'کلینیک الف', 'status' => 'active', 'plan_code' => 'free']);
        $this->tenantB = Tenant::query()->create(['code' => 'B-001', 'name' => 'کلینیک ب', 'status' => 'active', 'plan_code' => 'free']);
        $this->managerA = User::factory()->create(['mobile' => '09111111111', 'username' => 'manager_a']);
        $this->tenantA->users()->attach($this->managerA->id, ['role_id' => $managerRole->id, 'status' => 'active']);

        Branch::query()->create(['tenant_id' => $this->tenantA->id, 'code' => 'A-BRANCH', 'name' => 'شعبه الف']);
        Branch::query()->create(['tenant_id' => $this->tenantB->id, 'code' => 'B-BRANCH', 'name' => 'شعبه ب']);
    }

    public function test_manager_sees_only_branches_from_active_tenant(): void
    {
        $response = $this->actingAs($this->managerA)
            ->withSession(['active_tenant_id' => $this->tenantA->id])
            ->get('/clinic/branches');

        $response->assertOk();
        $response->assertSee('شعبه الف');
        $response->assertDontSee('شعبه ب');
    }

    public function test_manager_cannot_switch_to_a_tenant_where_they_are_not_a_member(): void
    {
        $response = $this->actingAs($this->managerA)
            ->withSession(['active_tenant_id' => $this->tenantA->id])
            ->post('/active-tenant/'.$this->tenantB->id);

        $response->assertForbidden();
        $this->assertEquals($this->tenantA->id, session('active_tenant_id'));
    }
}
