@extends('layouts.app', ['title' => 'ایجاد طرح درمان'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }} · {{ $patient->patient_no }}</span>
        <h1>ایجاد طرح درمان برای {{ $patient->fullName() }}</h1>
        <p class="muted">پس از ایجاد طرح، آیتم‌های درمانی و نمودار دندان در مراحل بعدی تکمیل می‌شوند.</p>
    </div>
    <a class="button button--ghost" href="{{ route('patients.show', ['patientId' => $patient->id]) }}">بازگشت به پرونده</a>
</div>

<section class="card card--wide">
    <form method="post" action="{{ route('treatment-plans.store') }}" class="form-grid" novalidate>
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <div class="field-grid">
            <div class="field field--full"><label for="title">عنوان طرح درمان *</label><input id="title" name="title" value="{{ old('title', 'طرح درمان اولیه') }}" required></div>
            <div class="field"><label for="status">وضعیت</label><select id="status" name="status"><option value="draft">پیش‌نویس</option><option value="active">فعال</option><option value="on_hold">معلق</option></select></div>
            <div class="field"><label for="started_on">تاریخ شروع</label><input id="started_on" type="date" name="started_on" value="{{ old('started_on') }}" dir="ltr"></div>
            <div class="field field--full"><label for="notes">توضیحات</label><textarea id="notes" name="notes" rows="4">{{ old('notes') }}</textarea></div>
        </div>
        <div class="form-section">
            <h2>مراحل آمادهٔ استفاده</h2>
            <div class="stage-chip-list">
                @foreach ($stages as $stage)<span class="stage-chip"><i style="background: {{ $stage->color ?: '#0891B2' }}"></i>{{ $stage->name }}</span>@endforeach
            </div>
        </div>
        <div class="form-actions"><button class="button button--primary" type="submit">ایجاد طرح درمان</button></div>
    </form>
</section>
@endsection
