@extends('layouts.patient')

@section('content')
    <header class="page-header dashboard-header">
        <div>
            <span class="eyebrow">{{ $tenant->name }}</span>
            <h1>سلام، {{ $account->patient->fullName() }}</h1>
            <p>خلاصه‌ای از نوبت‌ها، طرح‌های درمان و وضعیت مالی شما در کلینیک فعال.</p>
        </div>
        <span class="badge badge--info">پرتال بیمار</span>
    </header>

    <section class="stats-grid" aria-label="خلاصهٔ پرونده">
        <article class="stat-card">
            <span>نوبت‌های پیش رو</span>
            <strong>{{ $upcomingAppointments->count() }}</strong>
            <small>نوبت ثبت‌شده از امروز</small>
        </article>
        <article class="stat-card">
            <span>طرح‌های درمان فعال</span>
            <strong>{{ $activeTreatmentPlanCount }}</strong>
            <small>پیشنهادی، تأییدشده یا در حال انجام</small>
        </article>
        <article class="stat-card">
            <span>فاکتورهای باز</span>
            <strong>{{ $openInvoiceCount }}</strong>
            <small>برای بررسی جزئیات مالی</small>
        </article>
    </section>

    <section class="dashboard-grid dashboard-grid--wide">
        <article class="card">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">برنامه‌ریزی</span>
                    <h2>نوبت‌های پیش رو</h2>
                </div>
                <a class="button button--ghost button--small" href="{{ route('patient.appointments') }}">همهٔ نوبت‌ها</a>
            </div>

            @if ($upcomingAppointments->isEmpty())
                <div class="empty-state">
                    <strong>نوبت آینده‌ای ثبت نشده است.</strong>
                    <p>برای دریافت نوبت با کلینیک تماس بگیرید.</p>
                </div>
            @else
                <div class="stack-list">
                    @foreach ($upcomingAppointments as $appointment)
                        <article class="appointment-row">
                            <div>
                                <strong>{{ $appointment->title }}</strong>
                                <p>{{ \App\Support\JalaliDate::weekdayName($appointment->starts_at) }}، <bdi dir="ltr">{{ \App\Support\JalaliDate::format($appointment->starts_at) }} · {{ $appointment->starts_at->format('H:i') }}</bdi></p>
                            </div>
                            <span class="badge badge--info">{{ match($appointment->status) {
                                'confirmed' => 'تأییدشده',
                                'arrived' => 'پذیرش‌شده',
                                'in_treatment' => 'در حال درمان',
                                'completed' => 'تکمیل‌شده',
                                default => 'در انتظار تأیید',
                            } }}</span>
                        </article>
                    @endforeach
                </div>
            @endif
        </article>

        <aside class="card card--notice">
            <span class="notice-icon" aria-hidden="true">i</span>
            <div>
                <h2>دسترسی امن به اطلاعات</h2>
                <p>این پورتال فقط اطلاعات پروندهٔ شما در کلینیک فعال را نمایش می‌دهد. برای تغییر نوبت، ثبت پرداخت یا اصلاح اطلاعات شخصی با کلینیک تماس بگیرید.</p>
            </div>
        </aside>
    </section>
@endsection
