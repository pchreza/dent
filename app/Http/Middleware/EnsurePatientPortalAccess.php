<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PatientAccount;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePatientPortalAccess
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = $this->tenantContext->get();

        abort_if($user === null || $tenant === null, 403, 'دسترسی به پورتال بیمار مجاز نیست.');

        $account = PatientAccount::query()
            ->with('patient')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        abort_if($account === null || $account->patient->status !== 'active', 403, 'حساب بیمار برای کلینیک فعال در دسترس نیست.');

        if ($user->must_change_password && ! $request->routeIs('patient.password.*')) {
            return new RedirectResponse(route('patient.password.edit'));
        }

        $request->attributes->set('patient_account', $account);

        return $next($request);
    }
}
