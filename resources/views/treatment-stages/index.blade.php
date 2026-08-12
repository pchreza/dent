@extends('layouts.app', ['title' => 'مراحل درمان'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }}</span>
        <h1>مراحل درمان</h1>
        <p class="muted">مراحل پیش‌فرض و مراحل اختصاصی کلینیک را مدیریت کنید.</p>
    </div>
</div>

<section class="dashboard-grid">
    <article class="card">
        <div class="section-heading section-heading--compact"><h2>فهرست مراحل فعال</h2><span class="status-badge status-badge--info">{{ $stages->count() }}</span></div>
        <div class="stage-list">
            @forelse ($stages as $stage)
                <div class="stage-row"><span class="stage-dot" style="background: {{ $stage->color ?: '#0891B2' }}"></span><strong>{{ $stage->name }}</strong><small>{{ $stage->category ?: 'عمومی' }} · {{ $stage->tenant_id ? 'اختصاصی کلینیک' : 'پیش‌فرض سامانه' }}</small></div>
            @empty
                <p class="muted">مرحله‌ای تعریف نشده است.</p>
            @endforelse
        </div>
    </article>
    <article class="card">
        <h2>افزودن مرحلهٔ اختصاصی</h2>
        <form method="post" action="{{ route('treatment-stages.store') }}" class="stack-form">
            @csrf
            <div class="field"><label for="code">کد مرحله <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="code" name="code" value="{{ old('code') }}" required dir="ltr" placeholder="custom_stage"></div>
            <div class="field"><label for="name">نام مرحله <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="name" name="name" value="{{ old('name') }}" required placeholder="مثلاً قالب‌گیری"></div>
            <div class="field"><label for="category">دسته‌بندی</label><input id="category" name="category" value="{{ old('category') }}"></div>
            <div class="field"><label for="color">رنگ</label><input id="color" name="color" value="{{ old('color', '#0891B2') }}" dir="ltr" placeholder="#0891B2"></div>
            <button class="button button--primary" type="submit">افزودن مرحله</button>
        </form>
    </article>
</section>
@endsection
