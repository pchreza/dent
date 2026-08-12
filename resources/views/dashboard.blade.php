@extends('layouts.app', ['title' => 'داشبورد'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">نمای کلی سامانه</span>
        <h1>داشبورد</h1>
        <p class="muted">هستهٔ فاز عملیاتی آماده است؛ نقش فعال شما: <strong>{{ $currentRole ?: 'کاربر کلینیک' }}</strong>.</p>
    </div>
    <span class="status-badge status-badge--success">سامانه فعال</span>
</div>

@if ($availableTenants->isNotEmpty())
    <section class="card tenant-switcher" aria-labelledby="tenant-switcher-title">
        <div>
            <span class="eyebrow">محدودهٔ کاری</span>
            <h2 id="tenant-switcher-title">انتخاب کلینیک فعال</h2>
            <p class="muted">داده‌های پرونده، شعبه، نوبت و مالی در Tenant انتخاب‌شده نمایش داده می‌شوند.</p>
        </div>
        <form method="post" action="{{ route('active-tenant.store', ['tenantId' => $activeTenant?->id ?? $availableTenants->first()->id]) }}" class="tenant-switcher__form">
            @csrf
            <label class="sr-only" for="tenant_id">کلینیک</label>
            <select id="tenant_id" name="tenant_id" onchange="this.form.action = '{{ url('/active-tenant') }}/' + this.value; this.form.submit();">
                @foreach ($availableTenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected($activeTenant?->id === $tenant->id)>{{ $tenant->name }} — {{ $tenant->code }}</option>
                @endforeach
            </select>
        </form>
    </section>
@endif

<section class="metrics-grid" aria-label="شاخص‌های پایه">
    <article class="metric-card">
        <span class="metric-card__label">وضعیت نصب</span>
        <strong>تکمیل‌شده</strong>
        <small>ویزارد قفل شده است</small>
    </article>
    <article class="metric-card">
        <span class="metric-card__label">حساب جاری</span>
        <strong>{{ $currentRole ?: 'کاربر فعال' }}</strong>
        <small>{{ auth()->user()->mobile }}</small>
    </article>
    <article class="metric-card">
        <span class="metric-card__label">Tenant فعال</span>
        <strong>{{ $activeTenant?->name ?? 'انتخاب نشده' }}</strong>
        <small>{{ $activeTenant?->code ?? 'مدیریت سراسری' }}</small>
    </article>
    <article class="metric-card">
        <span class="metric-card__label">نسخهٔ هسته</span>
        <strong>فاز ۱</strong>
        <small>امنیت و دسترسی پایه</small>
    </article>
</section>

<section class="dashboard-grid">
    <article class="card">
        <div class="section-heading section-heading--compact">
            <div>
                <span class="eyebrow">قدم بعدی</span>
                <h2>ساخت هستهٔ مدیریت کلینیک</h2>
            </div>
            <span class="status-badge status-badge--info">در حال توسعه</span>
        </div>
        <p class="muted">در مرحلهٔ بعد، ساخت کلینیک، شعبه، نقش‌ها، مجوزهای عملیاتی و جداسازی Tenant به‌صورت قابل تست اضافه می‌شود.</p>
        <div class="progress-track" aria-label="پیشرفت فاز اول">
            <span style="width: 18%"></span>
        </div>
        <small class="muted">۱۸٪ از فاز اول</small>
    </article>

    <article class="card card--notice">
        <span class="notice-icon" aria-hidden="true">i</span>
        <div>
            <h2>یادآوری امنیتی</h2>
            <p class="muted">این محیط برای توسعه است. قبل از استفادهٔ عملیاتی، HTTPS، تنظیمات cPanel، بکاپ و سیاست رمز را بررسی کنید.</p>
        </div>
    </article>
</section>
@endsection
