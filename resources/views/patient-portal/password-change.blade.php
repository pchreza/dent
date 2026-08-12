@extends('layouts.patient')

@section('content')
    <header class="page-header">
        <div>
            <span class="eyebrow">امنیت حساب</span>
            <h1>تغییر رمز عبور</h1>
            <p>برای محافظت از اطلاعات درمانی و مالی خود، رمز اولیه را با یک رمز شخصی و امن جایگزین کنید.</p>
        </div>
    </header>

    <section class="card card--wide">
        <form method="post" action="{{ route('patient.password.update') }}" class="form-grid" novalidate>
            @csrf
            <div class="form-group form-group--full">
                <label for="password">رمز عبور جدید <span aria-label="الزامی">*</span></label>
                <input id="password" name="password" type="password" autocomplete="new-password" required aria-describedby="password-hint">
                <p id="password-hint" class="form-hint">حداقل ۱۰ کاراکتر انتخاب کنید و از رمز قابل حدس استفاده نکنید.</p>
            </div>
            <div class="form-group form-group--full">
                <label for="password_confirmation">تکرار رمز عبور جدید <span aria-label="الزامی">*</span></label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>
            <div class="form-actions form-group--full">
                <button class="button button--primary" type="submit"><x-ui.icon name="settings" size="18" /> ذخیرهٔ رمز جدید</button>
            </div>
        </form>
    </section>
@endsection
