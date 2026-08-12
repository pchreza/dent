@extends('layouts.app', ['title' => 'صدور فاکتور'])

@section('content')
<div class="page-header"><div><span class="eyebrow">{{ $tenant->name }}</span><h1>صدور فاکتور</h1><p class="muted">مبلغ نهایی بر اساس تعداد، قیمت واحد و تخفیف محاسبه می‌شود.</p></div><a class="button button--ghost" href="{{ route('invoices.index') }}">بازگشت</a></div>
<section class="card card--wide"><form method="post" action="{{ route('invoices.store') }}" class="form-grid" novalidate>@csrf
    <div class="field-grid">
        <div class="field field--full"><label for="patient_id">بیمار <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><select id="patient_id" name="patient_id" required><option value="">انتخاب بیمار</option>@foreach ($patients as $patient)<option value="{{ $patient->id }}" @selected(old('patient_id') == $patient->id)>{{ $patient->fullName() }} — {{ $patient->patient_no }}</option>@endforeach</select></div>
        <div class="field field--full"><label for="description">شرح خدمت <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="description" name="description" value="{{ old('description') }}" required placeholder="مثلاً عصب‌کشی دندان ۶"></div>
        <div class="field"><label for="quantity">تعداد <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="quantity" type="number" min="1" name="quantity" value="{{ old('quantity', 1) }}" required dir="ltr"></div>
        <div class="field"><label for="unit_price">قیمت واحد <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="unit_price" type="number" min="0" step="0.01" name="unit_price" value="{{ old('unit_price') }}" required dir="ltr"></div>
        <div class="field"><label for="discount">تخفیف</label><input id="discount" type="number" min="0" step="0.01" name="discount" value="{{ old('discount', 0) }}" dir="ltr"></div>
        <div class="field"><label for="issue_date">تاریخ صدور <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="issue_date" type="date" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" required dir="ltr"></div>
        <div class="field"><label for="due_date">تاریخ سررسید</label><input id="due_date" type="date" name="due_date" value="{{ old('due_date') }}" dir="ltr"></div>
        <div class="field field--full"><label for="notes">یادداشت</label><textarea id="notes" name="notes" rows="3">{{ old('notes') }}</textarea></div>
    </div>
    <div class="form-actions"><button class="button button--primary" type="submit">صدور فاکتور</button></div>
</form></section>
@endsection
