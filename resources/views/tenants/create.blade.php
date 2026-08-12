@extends('layouts.app', ['title' => 'افزودن کلینیک'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">پنل سوپرادمین</span>
        <h1>افزودن کلینیک</h1>
        <p class="muted">Tenant و حساب مدیر اولیه در یک تراکنش ساخته می‌شوند.</p>
    </div>
    <a class="button button--ghost" href="{{ route('tenants.index') }}">بازگشت</a>
</div>

<section class="card card--wide">
    <form method="post" action="{{ route('tenants.store') }}" class="form-grid" novalidate>
        @csrf
        <div class="form-section">
            <h2>اطلاعات کلینیک</h2>
            <div class="field-grid">
                <div class="field">
                    <label for="name">نام کلینیک *</label>
                    <input id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="code">کد یکتا *</label>
                    <input id="code" name="code" value="{{ old('code') }}" required dir="ltr" placeholder="CLINIC-001">
                </div>
                <div class="field">
                    <label for="plan_code">کد پلن *</label>
                    <input id="plan_code" name="plan_code" value="{{ old('plan_code', 'free') }}" required dir="ltr">
                </div>
                <div class="field">
                    <label for="starts_on">تاریخ شروع</label>
                    <input id="starts_on" type="date" name="starts_on" value="{{ old('starts_on') }}" dir="ltr">
                </div>
                <div class="field">
                    <label for="ends_on">تاریخ پایان</label>
                    <input id="ends_on" type="date" name="ends_on" value="{{ old('ends_on') }}" dir="ltr">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2>مدیر اولیهٔ کلینیک</h2>
            <div class="field-grid">
                <div class="field">
                    <label for="manager_name">نام و نام خانوادگی *</label>
                    <input id="manager_name" name="manager_name" value="{{ old('manager_name') }}" required>
                </div>
                <div class="field">
                    <label for="manager_mobile">شماره موبایل *</label>
                    <input id="manager_mobile" name="manager_mobile" value="{{ old('manager_mobile') }}" required dir="ltr" inputmode="tel">
                </div>
                <div class="field">
                    <label for="manager_username">نام کاربری *</label>
                    <input id="manager_username" name="manager_username" value="{{ old('manager_username') }}" required dir="ltr">
                </div>
                <div class="field">
                    <label for="manager_password">رمز اولیه *</label>
                    <input id="manager_password" type="password" name="manager_password" required minlength="10" dir="ltr" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="manager_password_confirmation">تکرار رمز اولیه *</label>
                    <input id="manager_password_confirmation" type="password" name="manager_password_confirmation" required minlength="10" dir="ltr" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="form-actions"><button class="button button--primary" type="submit">ساخت کلینیک و مدیر</button></div>
    </form>
</section>
@endsection
