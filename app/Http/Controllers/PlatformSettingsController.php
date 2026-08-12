<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAppearanceRequest;
use App\Support\AuditLogger;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PlatformSettingsController extends Controller
{
    public function __construct(
        private readonly PlatformSettings $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function appearance(): View
    {
        return view('admin.settings.appearance', [
            'defaultFont' => $this->settings->get('default_font', 'Vazirmatn'),
        ]);
    }

    public function updateAppearance(UpdateAppearanceRequest $request): RedirectResponse
    {
        $font = $request->validated('default_font');
        DB::table('platform_settings')->updateOrInsert(
            ['key' => 'default_font'],
            [
                'value' => json_encode($font, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $this->auditLogger->record(
            action: 'platform.appearance_updated',
            actorId: $request->user()->id,
            after: ['default_font' => $font],
            reason: 'تغییر فونت پیش‌فرض سامانه',
        );

        return back()->with('status', 'فونت پیش‌فرض سامانه به‌روزرسانی شد.');
    }
}
