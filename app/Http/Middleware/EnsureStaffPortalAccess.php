<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PatientAccount;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureStaffPortalAccess
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = $this->tenantContext->get();

        if ($user !== null && $tenant !== null && PatientAccount::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->exists()) {
            return new RedirectResponse(route('patient.dashboard'));
        }

        return $next($request);
    }
}
