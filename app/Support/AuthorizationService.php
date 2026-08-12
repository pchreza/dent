<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AuthorizationService
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function allows(User $user, string $permission, ?Tenant $tenant = null): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        $tenant ??= $this->tenantContext->get();

        if ($tenant === null) {
            return false;
        }

        $membership = DB::table('tenant_user')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first(['id', 'role_id']);

        if ($membership === null) {
            return false;
        }

        $permissionId = DB::table('permissions')->where('code', $permission)->value('id');

        if ($permissionId === null) {
            return false;
        }

        $override = DB::table('user_permissions')
            ->where('tenant_user_id', $membership->id)
            ->where('permission_id', $permissionId)
            ->value('allowed');

        if ($override !== null) {
            return (bool) $override;
        }

        if ($membership->role_id === null) {
            return false;
        }

        return (bool) DB::table('role_permissions')
            ->where('role_id', $membership->role_id)
            ->where('permission_id', $permissionId)
            ->where('allowed', true)
            ->exists();
    }
}
