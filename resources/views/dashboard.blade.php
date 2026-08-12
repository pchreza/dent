@extends('layouts.app', ['title' => 'داشبورد'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">نمای کلی سامانه</span>
        <h1>داشبورد</h1>
        <p class="muted">هستهٔ فاز اول آماده است؛ ماژول‌های عملیاتی طبق نقشه‌راه اضافه می‌شوند.</p>
    </div>
    <span class="status-badge status-badge--success">سامانه فعال</span>
</div>

<section class="metrics-grid" aria-label="شاخص‌های پایه">
    <article class="metric-card">
        <span class="metric-card__label">وضعیت نصب</span>
        <strong>تکمیل‌شده</strong>
        <small>ویزارد قفل شده است</small>
    </article>
    <article class="metric-card">
        <span class="metric-card__label">حساب جاری</span>
        <strong>{{ auth()->user()->isSystemAdmin() ? 'سوپرادمین' : 'کاربر فعال' }}</strong>
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
