@extends('layouts.app', ['title' => 'ثبت نوبت'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }}</span>
        <h1>ثبت نوبت جدید</h1>
        <p class="muted">زمان‌ها با منطقهٔ زمانی Asia/Tehran ذخیره و در تقویم شمسی نمایش داده می‌شوند.</p>
    </div>
    <a class="button button--ghost" href="{{ route('calendar.index') }}">بازگشت به تقویم</a>
</div>

<section class="card card--wide">
    <form method="post" action="{{ route('appointments.store') }}" class="form-grid" novalidate>
        @csrf
        <div class="field-grid">
            <div class="field field--full"><label for="patient_id">بیمار <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><select id="patient_id" name="patient_id" required><option value="">انتخاب بیمار</option>@foreach ($patients as $patient)<option value="{{ $patient->id }}" @selected(old('patient_id') == $patient->id)>{{ $patient->fullName() }} — {{ $patient->patient_no }}</option>@endforeach</select></div>
            <div class="field"><label for="title">عنوان نوبت <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="title" name="title" value="{{ old('title', 'ویزیت/درمان') }}" required></div>
            <div class="field"><label for="appointment_type">نوع نوبت</label><input id="appointment_type" name="appointment_type" value="{{ old('appointment_type') }}" placeholder="معاینه، عصب‌کشی، روکش..."></div>
            <div class="field"><label for="branch_id">شعبه</label><select id="branch_id" name="branch_id"><option value="">انتخاب شعبه</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
            <div class="field"><label for="practitioner_id">پزشک</label><select id="practitioner_id" name="practitioner_id"><option value="">تعیین نشده</option>@foreach ($practitioners as $practitioner)<option value="{{ $practitioner->id }}" @selected(old('practitioner_id') == $practitioner->id)>{{ $practitioner->user->name }}</option>@endforeach</select></div>
            <div class="field"><label for="starts_at">شروع <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required dir="ltr"></div>
            <div class="field"><label for="ends_at">پایان <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required dir="ltr"></div>
            <div class="field field--full"><label for="notes">یادداشت</label><textarea id="notes" name="notes" rows="4">{{ old('notes') }}</textarea></div>
        </div>
        <div class="form-actions"><button class="button button--primary" type="submit">ثبت نوبت</button></div>
    </form>
</section>
@endsection
