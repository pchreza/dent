<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use RuntimeException;

final class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function require(): Tenant
    {
        return $this->tenant ?? throw new RuntimeException('Tenant context is required for this operation.');
    }

    public function resolveFor(User $user, int $tenantId): Tenant
    {
        if ($user->isSystemAdmin()) {
            return Tenant::query()->findOrFail($tenantId);
        }

        $tenant = $user->tenants()
            ->where('tenants.id', $tenantId)
            ->wherePivot('tenant_user.status', 'active')
            ->first();

        if ($tenant === null) {
            abort(403, 'شما به این کلینیک دسترسی ندارید.');
        }

        return $tenant;
    }
}
