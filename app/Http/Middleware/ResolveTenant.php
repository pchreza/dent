<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenantId = $request->session()->get('active_tenant_id');

        if ($user !== null && $tenantId !== null) {
            $this->tenantContext->set($this->tenantContext->resolveFor($user, (int) $tenantId));
        }

        return $next($request);
    }
}
