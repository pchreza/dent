<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ActiveTenantController extends Controller
{
    public function store(Request $request, TenantContext $tenantContext, int $tenantId): RedirectResponse
    {
        $tenant = $tenantContext->resolveFor($request->user(), $tenantId);
        $request->session()->put('active_tenant_id', $tenant->id);

        return back()->with('status', "کلینیک «{$tenant->name}» فعال شد.");
    }
}
