@extends('layouts.app', ['title' => 'نصب اولیه سامانه'])

@section('content')
<div class="centered-page">
    <section class="card card--wide">
        <div class="section-heading">
            <div>
                <span class="eyebrow">مرحلهٔ ۱ از ۱</span>
                <h1>راه‌اندازی Disweb Dental SaaS</h1>
                <p class="muted">پیش‌نیازها را بررسی کنید و حساب سوپرادمین را بسازید. پس از تکمیل، مسیر نصب قفل می‌شود.</p>
            </div>
            <span class="status-badge status-badge--info">نصب امن</span>
        </div>

        <div class="requirements-grid" aria-label="وضعیت پیش‌نیازها">
            @foreach ($requirements as $label => $isReady)
                <div class="requirement {{ $isReady ? 'requirement--ready' : 'requirement--failed' }}">
                    <span aria-hidden="true">{{ $isReady ? '✓' : '!' }}</span>
                    <span>{{ $label }}</span>
                    <strong>{{ $isReady ? 'آماده' : 'نیازمند بررسی' }}</strong>
                </div>
            @endforeach
        </div>

        <form method="post" action="{{ route('install.store') }}" class="form-grid" novalidate>
            @csrf
            <div class="form-section">
                <h2>برند و تنظیمات سامانه</h2>
                <div class="field-grid">
                    <div class="field">
                        <label for="product_name">نام محصول <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                        <input id="product_name" name="product_name" value="{{ old('product_name', 'Disweb Dental SaaS') }}" required>
                    </div>
                    <div class="field">
                        <label for="brand_name">نام برند <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                        <input id="brand_name" name="brand_name" value="{{ old('brand_name', 'Disweb') }}" required>
                    </div>
                    <div class="field">
                        <label for="timezone">منطقهٔ زمانی <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                        <select id="timezone" name="timezone" required>
                            <option value="Asia/Tehran" @selected(old('timezone', 'Asia/Tehran') === 'Asia/Tehran')>ایران — تهران</option>
                            <option value="UTC" @selected(old('timezone') === 'UTC')>UTC</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2>ساخت سوپرادمین</h2>
                <div class="field-grid">
                    <div class="field">
                        <label for="admin_name">نام و نام خانوادگی <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                        <input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required autocomplete="name">
                    </div>
                    <div class="field">
                        <label for="mobile">شمارهٔ موبایل <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                        <input id="mobile" name="mobile" value="{{ old('mobile') }}" required inputmode="tel" dir="ltr" autocomplete="tel">
                    </div>
                    <div class="field">
                        <label for="username">نام کاربری <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                        <input id="username" name="username" value="{{ old('username', 'superadmin') }}" required dir="ltr" autocomplete="username">
                    </div>
                    <div class="field">
                        <label for="password">رمز عبور <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                        <input id="password" type="password" name="password" required minlength="10" autocomplete="new-password">
                        <small>حداقل ۱۰ کاراکتر.</small>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">تکرار رمز عبور <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required minlength="10" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button class="button button--primary" type="submit">اجرای نصب و قفل‌کردن سامانه</button>
            </div>
        </form>
    </section>
</div>
@endsection
