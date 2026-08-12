@extends('layouts.app', ['title' => 'ایجاد طرح درمان'])

@section('content')
@php($existingItems = old('items', [['stage_id' => '', 'treatment_id' => '', 'tooth_code' => $prefillTooth ?? '', 'surface_code' => $prefillSurface ?? 'all', 'status' => 'planned', 'priority' => 'normal', 'estimated_cost' => '', 'planned_on' => '', 'notes' => '']]))
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }} · پروندهٔ {{ $patient->patient_no }}</span>
        <h1>ایجاد طرح درمان آیتم‌محور</h1>
        <p class="muted">هر آیتم به‌صورت مستقل به مرحله، دندان، سطح و هزینهٔ برآوردی متصل می‌شود.</p>
    </div>
    <div class="inline-actions">
        <a class="button button--secondary" href="{{ route('dental-chart.show', ['patientId' => $patient->id]) }}">نمودار دندان</a>
        <a class="button button--ghost" href="{{ route('patients.show', ['patientId' => $patient->id]) }}">بازگشت به پرونده</a>
    </div>
</div>

<form method="post" action="{{ route('treatment-plans.store') }}" class="stack-form treatment-plan-form">
    @csrf
    <input type="hidden" name="patient_id" value="{{ $patient->id }}">

    <section class="card">
        <div class="section-heading"><div><span class="eyebrow">هدر طرح</span><h2>اطلاعات کلی</h2></div></div>
        <div class="form-grid">
            <div class="field field--wide"><label for="plan-title">عنوان طرح درمان</label><input id="plan-title" name="title" value="{{ old('title') }}" placeholder="مثلاً بازسازی دندان‌های خلفی" required></div>
            <div class="field"><label for="plan-status">وضعیت اولیه</label><select id="plan-status" name="status">@foreach (['draft' => 'پیش‌نویس', 'active' => 'فعال', 'on_hold' => 'متوقف', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده'] as $status => $label)<option value="{{ $status }}" @selected(old('status', 'draft') === $status)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="started-on">تاریخ شروع</label><input id="started-on" type="date" name="started_on" value="{{ old('started_on') }}" dir="ltr"></div>
            <div class="field field--wide"><label for="plan-notes">یادداشت کلی</label><textarea id="plan-notes" name="notes" rows="3">{{ old('notes') }}</textarea></div>
        </div>
    </section>

    @if ($prefillTooth)
        <div class="status-message status-message--info">آیتم نخست از نمودار دندان آماده شده است: <bdi dir="ltr">FDI {{ $prefillTooth }}</bdi> · {{ \App\Support\DentalToothPresenter::surfaceLabel($prefillSurface) }}</div>
    @endif

    <section class="card" aria-labelledby="treatment-items-title">
        <div class="section-heading">
            <div><span class="eyebrow">جزئیات اجرایی</span><h2 id="treatment-items-title">آیتم‌های درمان</h2></div>
            <button class="button button--secondary" type="button" data-treatment-item-add>افزودن آیتم</button>
        </div>
        <div class="treatment-item-list" data-treatment-items data-next-index="{{ count($existingItems) }}">
            @foreach ($existingItems as $index => $item)
                @include('treatment-plans.partials.item-fields', ['index' => $index, 'item' => $item, 'stages' => $stages, 'treatments' => $treatments])
            @endforeach
        </div>
        <template id="treatment-item-template">
            @include('treatment-plans.partials.item-fields', ['index' => '__INDEX__', 'item' => [], 'stages' => $stages, 'treatments' => $treatments])
        </template>
    </section>
    <div class="inline-actions"><button class="button button--primary" type="submit">ثبت طرح درمان</button></div>
</form>
@endsection
