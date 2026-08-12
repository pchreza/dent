<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NotificationController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(): View
    {
        $tenant = $this->tenantContext->require();
        $notifications = request()->user()->notifications()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('tenant', 'notifications'));
    }

    public function markRead(Request $request, int $notificationId): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $request->user()->notifications()
            ->where('tenant_id', $tenant->id)
            ->whereKey($notificationId)
            ->update(['status' => 'read', 'read_at' => now()]);

        return back();
    }
}
