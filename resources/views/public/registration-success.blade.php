@extends('layouts.app', ['title' => 'درخواست ثبت شد'])

@section('content')
<div class="auth-page">
    <section class="auth-card card">
        <div class="auth-card__intro">
            <span class="success-mark" aria-hidden="true">✓</span>
            <span class="eyebrow">درخواست دریافت شد</span>
            <h1>درخواست شما ثبت شد</h1>
            <p class="muted">اطلاعات شما برای بررسی به کلینیک ارسال شد. پس از تأیید، کارکنان کلینیک با شما تماس خواهند گرفت.</p>
        </div>
        <p class="auth-card__note">برای حفظ حریم خصوصی، جزئیات پرونده در این صفحه نمایش داده نمی‌شود.</p>
    </section>
</div>
@endsection
