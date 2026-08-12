<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Support\JalaliDate;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CalendarController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(Request $request): View
    {
        $tenant = $this->tenantContext->require();
        $anchor = CarbonImmutable::parse((string) $request->query('week', now()->toDateString()), config('app.timezone'));
        $week = JalaliDate::week($anchor);
        $from = $week[0]['date']->startOfDay();
        $to = $week[6]['date']->endOfDay();

        $appointments = Appointment::query()
            ->with(['patient', 'practitioner.user', 'branch'])
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Appointment $appointment): string => $appointment->starts_at->format('Y-m-d'));

        return view('calendar.index', [
            'tenant' => $tenant,
            'week' => $week,
            'appointments' => $appointments,
            'previousWeek' => $week[0]['date']->subWeek()->format('Y-m-d'),
            'nextWeek' => $week[6]['date']->addDay()->format('Y-m-d'),
        ]);
    }
}
