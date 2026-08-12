@extends('layouts.app', ['title' => 'ورود به سامانه'])

@section('content')
<div class="auth-page">
    <section class="auth-card card">
        <div class="auth-card__intro">
            <span class="brand-mark brand-mark--large" aria-hidden="true">D</span>
            <span class="eyebrow">Disweb Dental SaaS</span>
            <h1>ورود به سامانه</h1>
            <p class="muted">مدیریت یکپارچهٔ کلینیک، پرونده، نوبت و درمان.</p>
        </div>

        <form method="post" action="{{ route('login.store') }}" class="stack-form" novalidate>
            @csrf
            <div class="field">
                <label for="identifier">شمارهٔ موبایل یا نام کاربری</label>
                <input id="identifier" name="identifier" value="{{ old('identifier') }}" required autofocus autocomplete="username" dir="auto">
            </div>
            <div class="field">
                <label for="password">رمز عبور</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" dir="ltr">
            </div>
            <label class="checkbox-field">
                <input type="checkbox" name="remember" value="1">
                <span>مرا به خاطر بسپار</span>
            </label>
            <button class="button button--primary button--full" type="submit">ورود امن</button>
        </form>

        <p class="auth-card__note">اگر حساب شما هنوز توسط کلینیک فعال نشده است، با منشی یا مدیر کلینیک خود تماس بگیرید.</p>
    </section>
</div>
@endsection
