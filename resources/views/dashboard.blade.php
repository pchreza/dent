@extends('layouts.app', ['title' => 'داشبورد'])

@section('content')
@php($canCreateAppointment = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'scheduling.create'))
@php($appointmentStatusLabels = ['scheduled' => 'برنامه‌ریزی‌شده', 'confirmed' => 'تأییدشده', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده'])
<div class="page-header dashboard-header">
    <div>
        <span class="eyebrow">{{ $activeTenant?->name ?? 'نمای کلی سامانه' }}</span>
        <h1>سلام، {{ auth()->user()->name }}</h1>
        <p class="muted">{{ $activeTenant ? 'نمای عملیاتی کلینیک فعال و اولویت‌های امروز شما.' : 'برای مشاهدهٔ داده‌های عملیاتی، یک کلینیک فعال انتخاب کنید.' }}</p>
    </div>
    <div class="inline-actions">
        <span class="status-badge status-badge--success">{{ $currentRole ?: 'کاربر فعال' }}</span>
        @if ($canCreateAppointment && $activeTenant)
            <a class="button button--primary" href="{{ route('appointments.create') }}"><x-ui.icon name="plus" size="17" /> ثبت نوبت</a>
        @endif
    </div>
</div>

@if ($availableTenants->isNotEmpty())
    <section class="card tenant-switcher" aria-labelledby="tenant-switcher-title">
        <div>
            <span class="eyebrow">زمینهٔ کاری</span>
            <h2 id="tenant-switcher-title">کلینیک فعال</h2>
            <p class="muted">همهٔ داده‌ها و عملیات این صفحه به کلینیک انتخاب‌شده محدود هستند.</p>
        </div>
        <form method="post" action="{{ route('active-tenant.store', ['tenantId' => $activeTenant?->id ?? $availableTenants->first()->id]) }}" class="tenant-switcher__form">
            @csrf
            <label class="sr-only" for="tenant_id">کلینیک فعال</label>
            <select id="tenant_id" name="tenant_id" onchange="this.form.action = '{{ url('/active-tenant') }}/' + this.value; this.form.submit();">
                @foreach ($availableTenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected($activeTenant?->id === $tenant->id)>{{ $tenant->name }} — {{ $tenant->code }}</option>
                @endforeach
            </select>
        </form>
    </section>
@endif

<section class="metrics-grid" aria-label="شاخص‌های کلیدی">
    @if ($isSystemAdmin)
        <article class="metric-card">
            <span class="metric-card__label">کلینیک‌های قابل مدیریت</span>
            <strong dir="ltr"><bdi>{{ number_format($availableTenants->count()) }}</bdi></strong>
            <small>کلینیک فعال یا آزمایشی</small>
        </article>
    @endif

    @if ($activeTenant && $canViewPatients)
        <article class="metric-card">
            <span class="metric-card__label">بیماران ثبت‌شده</span>
            <strong dir="ltr"><bdi>{{ number_format($dashboardMetrics['patients_count'] ?? 0) }}</bdi></strong>
            <small>در کلینیک فعال</small>
        </article>
    @endif

    @if ($activeTenant && $canViewScheduling)
        <article class="metric-card">
            <span class="metric-card__label">نوبت‌های امروز</span>
            <strong dir="ltr"><bdi>{{ number_format($dashboardMetrics['today_appointments'] ?? 0) }}</bdi></strong>
            <small>به‌جز نوبت‌های لغوشده</small>
        </article>
    @endif

    @if ($activeTenant && $canViewFinance)
        <article class="metric-card">
            <span class="metric-card__label">ماندهٔ فاکتورهای باز</span>
            <strong dir="ltr"><bdi>{{ number_format((float) ($dashboardMetrics['outstanding_balance'] ?? 0)) }}</bdi></strong>
            <small>ریال</small>
        </article>
    @endif

    @if (! $activeTenant)
        <article class="metric-card">
            <span class="metric-card__label">وضعیت زمینهٔ کاری</span>
            <strong>انتخاب نشده</strong>
            <small>یک کلینیک را برای ادامه انتخاب کنید.</small>
        </article>
    @endif
</section>

<section class="dashboard-grid">
    <article class="card dashboard-activity" aria-labelledby="upcoming-appointments-title">
        <div class="section-heading section-heading--compact">
            <div>
                <span class="eyebrow">برنامه‌ریزی</span>
                <h2 id="upcoming-appointments-title">نوبت‌های پیش رو</h2>
            </div>
            @if ($canViewScheduling && $activeTenant)
                <a class="button button--ghost button--small" href="{{ route('calendar.index') }}">مشاهدهٔ تقویم</a>
            @endif
        </div>

        @if ($canViewScheduling && $activeTenant)
            <div class="dashboard-activity-list">
                @forelse ($upcomingAppointments as $appointment)
                    <article class="dashboard-activity-item">
                        <span class="dashboard-activity-item__date" dir="ltr"><bdi>{{ $appointment->starts_at ? \App\Support\JalaliDate::format($appointment->starts_at) : '—' }}</bdi></span>
                        <div>
                            <strong>{{ $appointment->patient?->fullName() ?? $appointment->title }}</strong>
                            <small>{{ $appointment->title }} · <span dir="ltr"><bdi>{{ $appointment->starts_at?->format('H:i') }}</bdi></span></small>
                        </div>
                        <span class="status-badge status-badge--info">{{ $appointmentStatusLabels[$appointment->status] ?? $appointment->status }}</span>
                    </article>
                @empty
                    <div class="dashboard-empty-state">
                        <strong>نوبت آینده‌ای ثبت نشده است.</strong>
                        <p>برای شروع برنامه‌ریزی، یک نوبت جدید ثبت کنید.</p>
                    </div>
                @endforelse
            </div>
        @else
            <div class="dashboard-empty-state">
                <strong>نمایش نوبت‌ها برای نقش فعلی مجاز نیست.</strong>
                <p>دسترسی‌های کلینیک توسط مدیر تنظیم می‌شوند.</p>
            </div>
        @endif
    </article>

    <article class="card dashboard-actions" aria-labelledby="dashboard-actions-title">
        <div class="section-heading section-heading--compact">
            <div>
                <span class="eyebrow">دسترسی سریع</span>
                <h2 id="dashboard-actions-title">اقدام‌های پرتکرار</h2>
            </div>
        </div>
        <div class="dashboard-actions__list">
            @if ($canCreateAppointment && $activeTenant)
                <a href="{{ route('appointments.create') }}"><x-ui.icon name="calendar" size="18" /><span>ثبت نوبت جدید</span><x-ui.icon name="chevron" size="16" /></a>
            @endif
            @if ($canViewPatients && $activeTenant)
                <a href="{{ route('patients.index') }}"><x-ui.icon name="patients" size="18" /><span>مدیریت بیماران</span><x-ui.icon name="chevron" size="16" /></a>
            @endif
            @if ($canViewFinance && $activeTenant)
                <a href="{{ route('invoices.index') }}"><x-ui.icon name="invoice" size="18" /><span>فاکتورها و پرداخت‌ها</span><x-ui.icon name="chevron" size="16" /></a>
            @endif
            @if ($isSystemAdmin)
                <a href="{{ route('tenants.index') }}"><x-ui.icon name="clinic" size="18" /><span>مدیریت کلینیک‌ها</span><x-ui.icon name="chevron" size="16" /></a>
            @endif
        </div>
    </article>
</section>
@endsection
