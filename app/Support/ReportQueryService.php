<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Tenant;
use App\Models\TreatmentCatalog;
use App\Models\TreatmentPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class ReportQueryService
{
    public const MAX_EXPORT_ROWS = 5000;

    private const STATUS_LABELS = [
        'active' => 'فعال',
        'archived' => 'بایگانی‌شده',
        'scheduled' => 'برنامه‌ریزی‌شده',
        'confirmed' => 'تأییدشده',
        'arrived' => 'حاضرشده',
        'in_treatment' => 'در حال درمان',
        'completed' => 'تکمیل‌شده',
        'cancelled' => 'لغوشده',
        'no_show' => 'عدم حضور',
        'draft' => 'پیش‌نویس',
        'proposed' => 'پیشنهادشده',
        'approved' => 'تأییدشده',
        'in_progress' => 'در حال انجام',
        'rejected' => 'ردشده',
        'issued' => 'صادرشده',
        'open' => 'باز',
        'partially_paid' => 'پرداخت ناقص',
        'paid' => 'تسویه‌شده',
        'overdue' => 'سررسیدگذشته',
        'waived' => 'بخشوده‌شده',
        'refunded' => 'مستردشده',
    ];

    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        return [
            'patients' => [
                'title' => 'گزارش بیماران',
                'description' => 'نمایش جمعیت بیماران و وضعیت ثبت آن‌ها در کلینیک.',
                'permission' => 'patients.view',
                'date_label' => 'بازهٔ تاریخ ایجاد',
                'columns' => [
                    ['key' => 'patient_no', 'label' => 'شماره بیمار', 'type' => 'ltr'],
                    ['key' => 'name', 'label' => 'نام بیمار', 'type' => 'text'],
                    ['key' => 'mobile', 'label' => 'موبایل', 'type' => 'ltr'],
                    ['key' => 'status', 'label' => 'وضعیت', 'type' => 'status'],
                    ['key' => 'insurance_name', 'label' => 'بیمه', 'type' => 'text'],
                    ['key' => 'created_at', 'label' => 'تاریخ ایجاد', 'type' => 'date'],
                ],
            ],
            'appointments' => [
                'title' => 'گزارش نوبت‌ها',
                'description' => 'تحلیل تعداد و نتیجهٔ نوبت‌ها بر اساس بازه، پزشک و شعبه.',
                'permission' => 'scheduling.view',
                'date_label' => 'بازهٔ زمان نوبت',
                'columns' => [
                    ['key' => 'starts_at', 'label' => 'زمان', 'type' => 'datetime'],
                    ['key' => 'patient', 'label' => 'بیمار', 'type' => 'text'],
                    ['key' => 'practitioner', 'label' => 'پزشک', 'type' => 'text'],
                    ['key' => 'branch', 'label' => 'شعبه', 'type' => 'text'],
                    ['key' => 'title', 'label' => 'عنوان', 'type' => 'text'],
                    ['key' => 'status', 'label' => 'وضعیت', 'type' => 'status'],
                ],
            ],
            'treatments' => [
                'title' => 'گزارش طرح‌های درمان',
                'description' => 'پیگیری وضعیت طرح‌های درمان و مبلغ برآوردی آن‌ها.',
                'permission' => 'treatments.view',
                'date_label' => 'بازهٔ تاریخ ثبت طرح',
                'columns' => [
                    ['key' => 'patient', 'label' => 'بیمار', 'type' => 'text'],
                    ['key' => 'title', 'label' => 'عنوان طرح', 'type' => 'text'],
                    ['key' => 'status', 'label' => 'وضعیت', 'type' => 'status'],
                    ['key' => 'started_on', 'label' => 'شروع', 'type' => 'date'],
                    ['key' => 'completed_on', 'label' => 'تکمیل', 'type' => 'date'],
                    ['key' => 'estimated_total', 'label' => 'مبلغ برآوردی', 'type' => 'money'],
                ],
            ],
            'finance' => [
                'title' => 'گزارش مالی',
                'description' => 'نمایش فاکتورها، پرداخت‌ها و ماندهٔ قابل وصول.',
                'permission' => 'finance.view',
                'date_label' => 'بازهٔ تاریخ صدور فاکتور',
                'columns' => [
                    ['key' => 'invoice_no', 'label' => 'شماره فاکتور', 'type' => 'ltr'],
                    ['key' => 'patient', 'label' => 'بیمار', 'type' => 'text'],
                    ['key' => 'issue_date', 'label' => 'تاریخ صدور', 'type' => 'date'],
                    ['key' => 'due_date', 'label' => 'سررسید', 'type' => 'date'],
                    ['key' => 'status', 'label' => 'وضعیت', 'type' => 'status'],
                    ['key' => 'total', 'label' => 'کل', 'type' => 'money'],
                    ['key' => 'paid_total', 'label' => 'پرداخت‌شده', 'type' => 'money'],
                    ['key' => 'balance', 'label' => 'مانده', 'type' => 'money'],
                ],
            ],
            'services' => [
                'title' => 'گزارش خدمات',
                'description' => 'مقایسهٔ تعداد و مبلغ خدمات ثبت‌شده در فاکتورها.',
                'permission' => 'finance.view',
                'date_label' => 'بازهٔ تاریخ صدور فاکتور',
                'columns' => [
                    ['key' => 'service', 'label' => 'خدمت', 'type' => 'text'],
                    ['key' => 'category', 'label' => 'دسته', 'type' => 'text'],
                    ['key' => 'quantity', 'label' => 'تعداد', 'type' => 'number'],
                    ['key' => 'unit_price', 'label' => 'قیمت واحد', 'type' => 'money'],
                    ['key' => 'total', 'label' => 'مبلغ کل', 'type' => 'money'],
                ],
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function visibleDefinitions(User $user, AuthorizationService $authorization): array
    {
        $visible = [];

        foreach ($this->definitions() as $code => $definition) {
            if ($authorization->allows($user, 'reports.view') && $authorization->allows($user, $definition['permission'])) {
                $visible[$code] = $definition;
            }
        }

        return $visible;
    }

    /** @return array<string, mixed> */
    public function run(string $code, Tenant $tenant, array $input): array
    {
        $definition = $this->definition($code);
        $filters = $this->normalizeFilters($input);

        $result = match ($code) {
            'patients' => $this->patients($tenant, $filters),
            'appointments' => $this->appointments($tenant, $filters),
            'treatments' => $this->treatments($tenant, $filters),
            'finance' => $this->finance($tenant, $filters),
            'services' => $this->services($tenant, $filters),
            default => abort(404),
        };

        return [
            'code' => $code,
            'definition' => $definition,
            'filters' => $filters,
            'kpis' => $result['kpis'],
            'rows' => $result['rows'],
            'totalRows' => $result['totalRows'],
            'tooManyRows' => $result['totalRows'] > self::MAX_EXPORT_ROWS,
            'generatedAt' => CarbonImmutable::now(config('app.timezone', 'Asia/Tehran')),
        ];
    }

    /** @return array<string, mixed> */
    public function options(Tenant $tenant): array
    {
        return [
            'branches' => Branch::query()->where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'practitioners' => Practitioner::query()->with('user:id,name')->where('tenant_id', $tenant->id)->where('is_active', true)->get(['id', 'user_id']),
            'treatments' => TreatmentCatalog::query()->where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'category']),
        ];
    }

    public function definition(string $code): array
    {
        $definition = $this->definitions()[$code] ?? null;

        abort_if($definition === null, 404);

        return $definition;
    }

    /** @return array<string, mixed> */
    private function normalizeFilters(array $input): array
    {
        $timezone = config('app.timezone', 'Asia/Tehran');
        $today = CarbonImmutable::now($timezone);
        $from = isset($input['from']) && $input['from'] !== ''
            ? JalaliDate::parse((string) $input['from'])
            : $today->startOfMonth();
        $to = isset($input['to']) && $input['to'] !== ''
            ? JalaliDate::parse((string) $input['to'])->endOfDay()
            : $today->endOfDay();

        return [
            'from' => $from,
            'to' => $to,
            'from_input' => JalaliDate::format($from),
            'to_input' => JalaliDate::format($to),
            'status' => filled($input['status'] ?? null) ? (string) $input['status'] : null,
            'branch_id' => filled($input['branch_id'] ?? null) ? (int) $input['branch_id'] : null,
            'practitioner_id' => filled($input['practitioner_id'] ?? null) ? (int) $input['practitioner_id'] : null,
            'treatment_id' => filled($input['treatment_id'] ?? null) ? (int) $input['treatment_id'] : null,
            'search' => filled($input['search'] ?? null) ? trim((string) $input['search']) : null,
            'method' => filled($input['method'] ?? null) ? trim((string) $input['method']) : null,
        ];
    }

    /** @param array<string, mixed> $filters */
    private function patients(Tenant $tenant, array $filters): array
    {
        $query = Patient::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$filters['from'], $filters['to']]);

        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }
        $this->applyPatientSearch($query, $filters['search']);

        return $this->result($query, [
            'کل بیماران' => (clone $query)->count(),
            'بیمار فعال' => (clone $query)->where('status', 'active')->count(),
            'بیمار بایگانی‌شده' => (clone $query)->where('status', 'archived')->count(),
        ], static fn (Patient $patient): array => [
            'patient_no' => $patient->patient_no,
            'name' => $patient->fullName(),
            'mobile' => $patient->mobile,
            'status' => self::statusLabel($patient->status),
            'insurance_name' => $patient->insurance_name ?: '—',
            'created_at' => JalaliDate::format($patient->created_at),
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function appointments(Tenant $tenant, array $filters): array
    {
        $query = Appointment::query()
            ->with(['patient:id,first_name,last_name', 'practitioner.user:id,name', 'branch:id,name'])
            ->where('tenant_id', $tenant->id)
            ->whereBetween('starts_at', [$filters['from'], $filters['to']]);

        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }
        if ($filters['branch_id'] !== null) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if ($filters['practitioner_id'] !== null) {
            $query->where('practitioner_id', $filters['practitioner_id']);
        }
        $this->applyPatientSearch($query, $filters['search'], 'patient');

        return $this->result($query->orderBy('starts_at'), [
            'کل نوبت‌ها' => (clone $query)->count(),
            'تکمیل‌شده' => (clone $query)->where('status', 'completed')->count(),
            'لغوشده' => (clone $query)->where('status', 'cancelled')->count(),
            'عدم حضور' => (clone $query)->where('status', 'no_show')->count(),
        ], static fn (Appointment $appointment): array => [
            'starts_at' => JalaliDate::format($appointment->starts_at).' '.$appointment->starts_at->format('H:i'),
            'patient' => $appointment->patient?->fullName() ?? '—',
            'practitioner' => $appointment->practitioner?->user?->name ?? 'تعیین نشده',
            'branch' => $appointment->branch?->name ?? '—',
            'title' => $appointment->title,
            'status' => self::statusLabel($appointment->status),
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function treatments(Tenant $tenant, array $filters): array
    {
        $query = TreatmentPlan::query()
            ->with('patient:id,first_name,last_name')
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$filters['from'], $filters['to']]);

        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }
        if ($filters['treatment_id'] !== null) {
            $query->whereHas('items', static fn (Builder $items): Builder => $items->where('treatment_id', $filters['treatment_id']));
        }
        $this->applyPatientSearch($query, $filters['search'], 'patient');

        return $this->result($query->latest('created_at'), [
            'کل طرح‌ها' => (clone $query)->count(),
            'در حال انجام' => (clone $query)->where('status', 'in_progress')->count(),
            'تکمیل‌شده' => (clone $query)->where('status', 'completed')->count(),
            'مبلغ برآوردی' => (float) (clone $query)->sum('estimated_total'),
        ], static fn (TreatmentPlan $plan): array => [
            'patient' => $plan->patient?->fullName() ?? '—',
            'title' => $plan->title,
            'status' => self::statusLabel($plan->status),
            'started_on' => $plan->started_on ? JalaliDate::format($plan->started_on) : '—',
            'completed_on' => $plan->completed_on ? JalaliDate::format($plan->completed_on) : '—',
            'estimated_total' => (float) $plan->estimated_total,
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function finance(Tenant $tenant, array $filters): array
    {
        $query = Invoice::query()
            ->with('patient:id,first_name,last_name')
            ->where('tenant_id', $tenant->id)
            ->whereBetween('issue_date', [$filters['from']->toDateString(), $filters['to']->toDateString()]);

        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }
        if ($filters['method'] !== null) {
            $query->whereHas('payments', static fn (Builder $payments): Builder => $payments->where('method', $filters['method']));
        }
        $this->applyPatientSearch($query, $filters['search'], 'patient');

        return $this->result($query->latest('issue_date'), [
            'کل فاکتورها' => (clone $query)->count(),
            'مبلغ صورتحساب' => (float) (clone $query)->sum('total'),
            'وصول‌شده' => (float) (clone $query)->sum('paid_total'),
            'مانده' => (float) (clone $query)->sum('total') - (float) (clone $query)->sum('paid_total'),
        ], static fn (Invoice $invoice): array => [
            'invoice_no' => $invoice->invoice_no,
            'patient' => $invoice->patient?->fullName() ?? '—',
            'issue_date' => JalaliDate::format($invoice->issue_date),
            'due_date' => $invoice->due_date ? JalaliDate::format($invoice->due_date) : '—',
            'status' => self::statusLabel($invoice->status),
            'total' => (float) $invoice->total,
            'paid_total' => (float) $invoice->paid_total,
            'balance' => max(0, (float) $invoice->total - (float) $invoice->paid_total),
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function services(Tenant $tenant, array $filters): array
    {
        $query = InvoiceItem::query()
            ->with(['invoice.patient:id,first_name,last_name', 'treatment:id,name,category'])
            ->where('tenant_id', $tenant->id)
            ->whereHas('invoice', function (Builder $invoice) use ($tenant, $filters): void {
                $invoice->where('tenant_id', $tenant->id)
                    ->whereBetween('issue_date', [$filters['from']->toDateString(), $filters['to']->toDateString()]);
            });

        if ($filters['treatment_id'] !== null) {
            $query->where('treatment_id', $filters['treatment_id']);
        }
        if ($filters['search'] !== null) {
            $query->where(function (Builder $items) use ($filters): void {
                $items->where('description', 'like', '%'.$filters['search'].'%')
                    ->orWhereHas('invoice.patient', fn (Builder $patient): Builder => $this->patientNameSearch($patient, $filters['search']));
            });
        }

        return $this->result($query->latest('id'), [
            'تعداد ردیف خدمت' => (clone $query)->count(),
            'تعداد فاکتور' => (clone $query)->distinct('invoice_id')->count('invoice_id'),
            'مبلغ خدمات' => (float) (clone $query)->sum('total'),
        ], static fn (InvoiceItem $item): array => [
            'service' => $item->treatment?->name ?? $item->description,
            'category' => $item->treatment?->category ?: '—',
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'total' => (float) $item->total,
        ]);
    }

    /** @param Builder<Model> $query @param array<string, mixed> $kpis */
    private function result(Builder $query, array $kpis, callable $mapper): array
    {
        $totalRows = (clone $query)->count();
        $rows = $query->limit(self::MAX_EXPORT_ROWS + 1)->get()->take(self::MAX_EXPORT_ROWS)->map($mapper)->values()->all();

        return ['kpis' => $kpis, 'rows' => $rows, 'totalRows' => $totalRows];
    }

    private function applyPatientSearch(Builder $query, ?string $search, string $relation = ''): void
    {
        if ($search === null) {
            return;
        }

        $relation === ''
            ? $this->patientNameSearch($query, $search)
            : $query->whereHas($relation, fn (Builder $patient): Builder => $this->patientNameSearch($patient, $search));
    }

    private function patientNameSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $patient) use ($search): void {
            $like = '%'.$search.'%';
            $patient->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('mobile', 'like', $like)
                ->orWhere('patient_no', 'like', $like);
        });
    }

    private static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status ?? ''] ?? ($status ?: '—');
    }
}
