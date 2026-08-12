@extends('layouts.app', ['title' => 'افزودن شعبه'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }}</span>
        <h1>افزودن شعبه</h1>
        <p class="muted">شعبه به کلینیک فعال متصل می‌شود و از سایر Tenantها جدا خواهد بود.</p>
    </div>
    <a class="button button--ghost" href="{{ route('branches.index') }}">بازگشت</a>
</div>

<section class="card card--wide">
    <form method="post" action="{{ route('branches.store') }}" class="form-grid" novalidate>
        @csrf
        <div class="field-grid">
            <div class="field">
                <label for="name">نام شعبه *</label>
                <input id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="field">
                <label for="code">کد شعبه *</label>
                <input id="code" name="code" value="{{ old('code') }}" required dir="ltr" placeholder="BRANCH-01">
            </div>
            <div class="field">
                <label for="phone">تلفن</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" dir="ltr" inputmode="tel">
            </div>
            <div class="field field--full">
                <label for="address">نشانی</label>
                <textarea id="address" name="address" rows="3">{{ old('address') }}</textarea>
            </div>
        </div>
        <div class="form-actions"><button class="button button--primary" type="submit">ثبت شعبه</button></div>
    </form>
</section>
@endsection
