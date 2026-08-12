@extends('layouts.app', ['title' => 'ورود به سامانه'])

@section('content')
<div class="auth-page">
    <section class="auth-card card" aria-labelledby="login-title">
        <div class="auth-card__intro">
            <span class="brand-mark brand-mark--large" aria-hidden="true">D</span>
            <span class="eyebrow">{{ app(\App\Support\PlatformSettings::class)->get('brand_name', 'Disweb') }}</span>
            <h1 id="login-title">ورود به سامانه</h1>
            <p class="muted">مدیریت یکپارچهٔ کلینیک، پرونده، نوبت و درمان.</p>
        </div>

        <form method="post" action="{{ route('login.store') }}" class="stack-form" novalidate>
            @csrf
            <div class="field">
                <label for="identifier">شمارهٔ موبایل یا نام کاربری <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                <input id="identifier" name="identifier" value="{{ old('identifier') }}" autocomplete="username" required autofocus dir="auto" inputmode="text" placeholder="مثال: 0912… یا نام کاربری">
            </div>
            <div class="field">
                <label for="password">رمز عبور <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                <input id="password" name="password" type="password" autocomplete="current-password" required dir="ltr" placeholder="رمز عبور خود را وارد کنید">
            </div>
            <label class="checkbox-field" for="remember">
                <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>مرا به خاطر بسپار</span>
            </label>
            <button class="button button--primary button--full" type="submit">ورود امن</button>
        </form>

        <p class="auth-card__note">اگر حساب شما هنوز توسط کلینیک فعال نشده است، با منشی یا مدیر کلینیک خود تماس بگیرید.</p>
    </section>
</div>
@endsection
