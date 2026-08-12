<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(TenantContext $tenantContext): View
    {
        return view('dashboard', [
            'activeTenant' => $tenantContext->get(),
        ]);
    }
}
