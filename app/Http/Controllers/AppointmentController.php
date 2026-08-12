<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentStatusHistory;
use App\Models\Notification;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class AppointmentController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function create(): View
    {
        $tenant = $this->tenantContext->require();

        return view('appointments.create', [
            'tenant' => $tenant,
            'patients' => $tenant->patients()->where('status', 'active')->orderBy('last_name')->orderBy('first_name')->get(),
            'branches' => $tenant->branches()->where('is_active', true)->orderBy('name')->get(),
            'practitioners' => $tenant->practitioners()->with('user')->where('is_active', true)->get(),
        ]);
    }

    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $data = $request->validated();
        $startsAt = CarbonImmutable::parse($data['starts_at'], config('app.timezone'));
        $endsAt = CarbonImmutable::parse($data['ends_at'], config('app.timezone'));

        $patient = $tenant->patients()->whereKey($data['patient_id'])->firstOrFail();
        $practitionerId = $this->scopedId($tenant->id, 'practitioners', $data['practitioner_id'] ?? null);
        $branchId = $this->scopedId($tenant->id, 'branches', $data['branch_id'] ?? null);

        $overlap = Appointment::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['cancelled'])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($practitionerId !== null, fn ($query) => $query->where('practitioner_id', $practitionerId))
            ->when($practitionerId === null && $branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->exists();

        if ($overlap) {
            return back()->withInput()->withErrors(['starts_at' => 'این بازه با نوبت دیگری هم‌پوشانی دارد.']);
        }

        $appointment = DB::transaction(function () use ($tenant, $request, $data, $patient, $practitionerId, $branchId, $startsAt, $endsAt): Appointment {
            $appointment = Appointment::query()->create([
                'tenant_id' => $tenant->id,
                'patient_id' => $patient->id,
                'practitioner_id' => $practitionerId,
                'branch_id' => $branchId,
                'title' => $data['title'],
                'appointment_type' => $data['appointment_type'] ?? null,
                'status' => 'scheduled',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            AppointmentStatusHistory::query()->create([
                'tenant_id' => $tenant->id,
                'appointment_id' => $appointment->id,
                'to_status' => 'scheduled',
                'changed_by' => $request->user()->id,
            ]);

            DB::table('tenant_user')
                ->join('users', 'users.id', '=', 'tenant_user.user_id')
                ->where('tenant_user.tenant_id', $tenant->id)
                ->where('tenant_user.status', 'active')
                ->select('users.id')
                ->distinct()
                ->get()
                ->each(function (object $recipient) use ($tenant, $patient): void {
                    Notification::query()->create([
                        'tenant_id' => $tenant->id,
                        'recipient_id' => $recipient->id,
                        'type' => 'appointment.created',
                        'title' => 'نوبت جدید ثبت شد',
                        'body' => "برای بیمار {$patient->fullName()} یک نوبت جدید ثبت شد.",
                        'status' => 'unread',
                        'action_url' => route('calendar.index'),
                        'expires_at' => now()->addDays(30),
                    ]);
                });

            return $appointment;
        });

        $this->auditLogger->record(
            action: 'appointment.created',
            tenantId: $tenant->id,
            subjectType: Appointment::class,
            subjectId: $appointment->id,
            after: ['patient_id' => $patient->id, 'starts_at' => $startsAt->toIso8601String()],
            reason: 'ثبت نوبت از تقویم کلینیک',
        );

        return redirect()->route('calendar.index')->with('status', 'نوبت با موفقیت ثبت شد.');
    }

    private function scopedId(int $tenantId, string $table, ?int $id): ?int
    {
        if ($id === null) {
            return null;
        }

        return (int) DB::table($table)->where('tenant_id', $tenantId)->where('id', $id)->value('id') ?: abort(404);
    }
}
