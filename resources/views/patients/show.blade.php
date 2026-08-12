@extends('layouts.app', ['title' => 'پروندهٔ بیمار'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }} · پروندهٔ {{ $patient->patient_no }}</span>
        <h1>{{ $patient->fullName() }}</h1>
        <p class="muted">آخرین به‌روزرسانی: <span dir="ltr"><bdi>{{ $patient->updated_at?->format('Y-m-d H:i') }}</bdi></span></p>
    </div>
    <a class="button button--ghost" href="{{ route('patients.index') }}">بازگشت به بیماران</a>
</div>

@if ($patient->hasCriticalAllergy())
    <div class="critical-alert" role="alert"><strong>هشدار حساسیت بحرانی:</strong> پیش از هر اقدام درمانی حساسیت‌های بیمار را بررسی کنید.</div>
@endif

<section class="patient-overview-grid">
    <article class="card"><span class="metric-card__label">موبایل</span><strong dir="ltr"><bdi>{{ $patient->mobile }}</bdi></strong><small>{{ $patient->insurance_name ?: 'بیمه ثبت نشده' }}</small></article>
    <article class="card"><span class="metric-card__label">کد ملی</span><strong dir="ltr"><bdi>{{ $patient->national_id }}</bdi></strong><small>{{ $patient->birth_date?->format('Y-m-d') ?: 'تاریخ تولد ثبت نشده' }}</small></article>
    <article class="card"><span class="metric-card__label">وضعیت پرونده</span><strong>{{ $patient->status }}</strong><small>{{ $patient->verified_at ? 'تأییدشده' : 'در انتظار تأیید' }}</small></article>
</section>

<section class="patient-detail-grid">
    <article class="card">
        <div class="section-heading section-heading--compact"><h2>حساسیت‌ها</h2><span class="status-badge status-badge--danger">{{ $patient->allergies->count() }}</span></div>
        @forelse ($patient->allergies as $allergy)
            <div class="detail-row"><strong>{{ $allergy->substance_name }}</strong><span>{{ $allergy->reaction ?: 'واکنش ثبت نشده' }}</span><span>{{ $allergy->is_critical ? 'بحرانی' : 'عادی' }}</span></div>
        @empty
            <p class="muted">حساسیتی ثبت نشده است.</p>
        @endforelse
    </article>
    <article class="card">
        <div class="section-heading section-heading--compact"><h2>داروهای فعال</h2><span class="status-badge status-badge--info">{{ $patient->medications->where('is_active', true)->count() }}</span></div>
        @forelse ($patient->medications->where('is_active', true) as $medication)
            <div class="detail-row"><strong>{{ $medication->name }}</strong><span>{{ $medication->dosage ?: '—' }}</span><span>{{ $medication->frequency ?: '—' }}</span></div>
        @empty
            <p class="muted">داروی فعالی ثبت نشده است.</p>
        @endforelse
    </article>
    <article class="card">
        <div class="section-heading section-heading--compact"><h2>سوابق پزشکی</h2><span class="status-badge status-badge--info">{{ $patient->conditions->count() }}</span></div>
        @forelse ($patient->conditions as $condition)
            <div class="detail-row"><strong>{{ $condition->condition?->name ?: '—' }}</strong><span>{{ $condition->value ?: '—' }}</span></div>
        @empty
            <p class="muted">سابقه‌ای ثبت نشده است.</p>
        @endforelse
    </article>
    <article class="card">
        <div class="section-heading section-heading--compact"><h2>یادداشت‌ها</h2><span class="status-badge status-badge--info">{{ $patient->notes->count() }}</span></div>
        @forelse ($patient->notes as $note)
            <div class="note-block"><strong>{{ $note->author?->name ?: 'کاربر سامانه' }}</strong><small>{{ $note->created_at?->format('Y-m-d H:i') }}</small><p>{{ $note->body }}</p></div>
        @empty
            <p class="muted">یادداشتی ثبت نشده است.</p>
        @endforelse
    </article>
</section>
@endsection
