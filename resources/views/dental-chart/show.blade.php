@extends('layouts.app', ['title' => 'وضعیت دندان‌ها'])

@section('content')
@php
    $canEditDental = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'dentistry.update');
    $canCreateTreatment = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'treatments.create');
    $validSelectedTooth = in_array($selectedTooth, \App\Models\DentalChartEntry::allToothCodes(), true) ? $selectedTooth : '';
    $validSelectedSurface = in_array($selectedSurface, \App\Models\DentalChartEntry::SURFACES, true) ? $selectedSurface : 'all';
@endphp

<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }} · پروندهٔ {{ $patient->patient_no }}</span>
        <h1>وضعیت دندان‌ها</h1>
        <p class="muted">مرور سریع وضعیت ثبت‌شدهٔ دندان‌ها و ثبت مورد جدید، بدون نمودار یا جزئیات اضافی.</p>
    </div>
    <div class="inline-actions">
        <a class="button button--secondary" href="{{ route('patients.show', ['patientId' => $patient->id]) }}">بازگشت به پرونده</a>
        @if ($canCreateTreatment)
            <a class="button button--primary" href="{{ route('treatment-plans.create', ['patientId' => $patient->id, 'tooth' => $validSelectedTooth ?: null, 'surface' => $validSelectedSurface]) }}">ایجاد طرح درمان</a>
        @endif
    </div>
</div>

<section class="dental-minimal-summary" aria-label="خلاصهٔ وضعیت‌های ثبت‌شده">
    <article><span>دندان‌های دارای وضعیت</span><strong dir="ltr"><bdi>{{ $latestEntries->count() }}</bdi></strong></article>
    <article><span>کل رویدادهای ثبت‌شده</span><strong dir="ltr"><bdi>{{ $history->count() }}</bdi></strong></article>
</section>

<section class="dental-minimal-layout">
    <article class="card dental-status-card">
        <div class="section-heading">
            <div><span class="eyebrow">آخرین وضعیت هر دندان</span><h2>دندان‌های ثبت‌شده</h2></div>
            <span class="muted">برای مشاهدهٔ همهٔ سابقه‌ها، جدول پایین صفحه را ببینید.</span>
        </div>

        @forelse ($latestEntries as $entry)
            @php($tooth = \App\Support\DentalToothPresenter::present($entry->tooth_code))
            <article class="dental-status-row {{ $validSelectedTooth === $entry->tooth_code ? 'is-selected' : '' }}">
                <div class="dental-status-row__code" dir="ltr"><bdi>{{ $entry->tooth_code }}</bdi></div>
                <div class="dental-status-row__details">
                    <strong>{{ $tooth['short_name'] }}</strong>
                    <span>{{ \App\Models\DentalChartEntry::STATUSES[$entry->status_code] ?? $entry->status_code }} · {{ \App\Support\DentalToothPresenter::surfaceLabel($entry->surface_code) }}</span>
                    @if ($entry->note)<small>{{ $entry->note }}</small>@endif
                </div>
                <time dir="ltr"><bdi>{{ \App\Support\JalaliDate::format($entry->created_at) }}</bdi></time>
                <div class="dental-status-row__actions">
                    <a class="button button--small button--secondary" href="{{ route('dental-chart.show', ['patientId' => $patient->id, 'tooth' => $entry->tooth_code, 'surface' => $entry->surface_code]) }}#quick-entry">ثبت وضعیت</a>
                    @if ($canCreateTreatment)
                        <a class="button button--small button--ghost" href="{{ route('treatment-plans.create', ['patientId' => $patient->id, 'tooth' => $entry->tooth_code, 'surface' => $entry->surface_code]) }}">طرح درمان</a>
                    @endif
                </div>
            </article>
        @empty
            <div class="dental-minimal-empty">
                <strong>هنوز وضعیت دندانی ثبت نشده است.</strong>
                <p>از فرم سادهٔ کنار صفحه، اولین وضعیت دندان را ثبت کنید.</p>
            </div>
        @endforelse
    </article>

    @if ($canEditDental)
        <aside class="card dental-quick-entry" id="quick-entry">
            <div class="section-heading section-heading--compact"><div><span class="eyebrow">ثبت سریع</span><h2>ثبت وضعیت دندان</h2></div></div>
            <form method="post" action="{{ route('dental-chart.store', ['patientId' => $patient->id]) }}" class="stack-form">
                @csrf
                <div class="field">
                    <label for="tooth-code">دندان</label>
                    <select id="tooth-code" name="tooth_code" required>
                        <option value="">انتخاب دندان</option>
                        @foreach (\App\Models\DentalChartEntry::allToothCodes() as $toothCode)
                            @php($toothLabel = \App\Support\DentalToothPresenter::present($toothCode))
                            <option value="{{ $toothCode }}" @selected(old('tooth_code', $validSelectedTooth) === $toothCode)><bdi dir="ltr">{{ $toothCode }}</bdi> — {{ $toothLabel['short_name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="surface-code">سطح</label>
                    <select id="surface-code" name="surface_code" required>
                        @foreach (\App\Models\DentalChartEntry::SURFACES as $surfaceCode)
                            <option value="{{ $surfaceCode }}" @selected(old('surface_code', $validSelectedSurface) === $surfaceCode)>{{ \App\Support\DentalToothPresenter::surfaceLabel($surfaceCode) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="status-code">وضعیت</label>
                    <select id="status-code" name="status_code" required>
                        <option value="">انتخاب وضعیت</option>
                        @foreach (\App\Models\DentalChartEntry::STATUSES as $statusCode => $label)
                            <option value="{{ $statusCode }}" @selected(old('status_code') === $statusCode)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="dental-note">یادداشت کوتاه</label>
                    <textarea id="dental-note" name="note" rows="3" placeholder="در صورت نیاز، توضیح مختصر ثبت کنید.">{{ old('note') }}</textarea>
                </div>
                <button class="button button--primary" type="submit">ذخیرهٔ وضعیت</button>
            </form>
        </aside>
    @endif
</section>

<section class="card dental-history-card">
    <div class="section-heading"><div><span class="eyebrow">سوابق ثبت‌شده</span><h2>تاریخچهٔ کامل وضعیت دندان‌ها</h2></div></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>دندان</th><th>نام</th><th>سطح</th><th>وضعیت</th><th>یادداشت</th><th>ثبت‌کننده</th><th>تاریخ</th></tr></thead>
            <tbody>
                @forelse ($history as $entry)
                    @php($tooth = \App\Support\DentalToothPresenter::present($entry->tooth_code))
                    <tr>
                        <td dir="ltr"><bdi>{{ $entry->tooth_code }}</bdi></td>
                        <td>{{ $tooth['short_name'] }}</td>
                        <td>{{ \App\Support\DentalToothPresenter::surfaceLabel($entry->surface_code) }}</td>
                        <td>{{ \App\Models\DentalChartEntry::STATUSES[$entry->status_code] ?? $entry->status_code }}</td>
                        <td>{{ $entry->note ?: '—' }}</td>
                        <td>{{ $entry->recorder?->name ?: 'کاربر سامانه' }}</td>
                        <td dir="ltr"><bdi>{{ \App\Support\JalaliDate::format($entry->created_at).' · '.$entry->created_at->format('H:i') }}</bdi></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">هنوز رویدادی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
