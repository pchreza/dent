@extends('layouts.app', ['title' => 'نمودار دندان'])

@section('content')
@php
    $canEditDental = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'dentistry.update');
    $upperPermanent = array_slice(\App\Models\DentalChartEntry::PERMANENT_TEETH, 0, 16);
    $lowerPermanent = array_slice(\App\Models\DentalChartEntry::PERMANENT_TEETH, 16);
    $upperPrimary = array_slice(\App\Models\DentalChartEntry::PRIMARY_TEETH, 0, 10);
    $lowerPrimary = array_slice(\App\Models\DentalChartEntry::PRIMARY_TEETH, 10);
@endphp
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }} · پروندهٔ {{ $patient->patient_no }}</span>
        <h1>نمودار دندان {{ $patient->fullName() }}</h1>
        <p class="muted">برای هر تغییر یک رویداد جدید ثبت می‌شود؛ تاریخچهٔ بالینی قبلی حذف یا بازنویسی نمی‌شود.</p>
    </div>
    <div class="inline-actions">
        <a class="button button--secondary" href="{{ route('patients.show', ['patientId' => $patient->id]) }}">بازگشت به پرونده</a>
        <a class="button button--ghost" href="{{ route('patients.index') }}">فهرست بیماران</a>
    </div>
</div>

<section class="dental-chart-layout">
    <article class="card dental-chart-card" aria-labelledby="dental-chart-title">
        <div class="section-heading"><div><span class="eyebrow">FDI</span><h2 id="dental-chart-title">وضعیت فعلی دندان‌ها</h2></div><span class="status-badge status-badge--info">{{ $history->count() }} رویداد</span></div>
        <p class="muted dental-chart-help">یک دندان را انتخاب کنید تا کد آن در فرم ثبت وضعیت قرار گیرد. رنگ هر خانه آخرین وضعیت ثبت‌شده را نشان می‌دهد.</p>

        @foreach ([['title' => 'دائمی · فک بالا', 'teeth' => $upperPermanent], ['title' => 'دائمی · فک پایین', 'teeth' => $lowerPermanent], ['title' => 'شیری · فک بالا', 'teeth' => $upperPrimary], ['title' => 'شیری · فک پایین', 'teeth' => $lowerPrimary]] as $arch)
            <section class="dental-arch" aria-label="{{ $arch['title'] }}">
                <h3>{{ $arch['title'] }}</h3>
                <div class="tooth-grid">
                    @foreach ($arch['teeth'] as $toothCode)
                        @php
                            $entry = $currentEntries->get("{$toothCode}:all") ?? $currentEntries->first(static fn ($item, $key): bool => str_starts_with($key, "{$toothCode}:"));
                            $status = $entry?->status_code ?? 'healthy';
                        @endphp
                        <button type="button" class="tooth-cell tooth-cell--{{ $status }}" data-dental-tooth="{{ $toothCode }}" aria-label="دندان {{ $toothCode }}، {{ \App\Models\DentalChartEntry::STATUSES[$status] ?? 'بدون وضعیت' }}">
                            <strong dir="ltr"><bdi>{{ $toothCode }}</bdi></strong>
                            <small>{{ \App\Models\DentalChartEntry::STATUSES[$status] ?? 'ثبت نشده' }}</small>
                        </button>
                    @endforeach
                </div>
            </section>
        @endforeach
    </article>

    <aside class="card dental-entry-card" id="chart-entry">
        <div class="section-heading"><div><span class="eyebrow">ثبت افزایشی</span><h2>ثبت وضعیت دندان</h2></div></div>
        @if ($canEditDental)
            <form method="post" action="{{ route('dental-chart.store', ['patientId' => $patient->id]) }}" class="stack-form">
                @csrf
                <div class="field"><label for="tooth-code">کد دندان</label>
                    <select id="tooth-code" name="tooth_code" data-dental-tooth-input required dir="ltr">
                        <option value="">انتخاب دندان</option>
                        <optgroup label="دندان‌های دائمی">
                            @foreach (\App\Models\DentalChartEntry::PERMANENT_TEETH as $toothCode)<option value="{{ $toothCode }}" @selected(old('tooth_code') === $toothCode)>{{ $toothCode }}</option>@endforeach
                        </optgroup>
                        <optgroup label="دندان‌های شیری">
                            @foreach (\App\Models\DentalChartEntry::PRIMARY_TEETH as $toothCode)<option value="{{ $toothCode }}" @selected(old('tooth_code') === $toothCode)>{{ $toothCode }}</option>@endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="field"><label for="surface-code">سطح دندان</label><select id="surface-code" name="surface_code" required><option value="all">کل دندان</option>@foreach (array_slice(\App\Models\DentalChartEntry::SURFACES, 1) as $surface)<option value="{{ $surface }}" @selected(old('surface_code') === $surface) dir="ltr">{{ $surface }}</option>@endforeach</select></div>
                <div class="field"><label for="status-code">وضعیت بالینی</label><select id="status-code" name="status_code" required><option value="">انتخاب وضعیت</option>@foreach (\App\Models\DentalChartEntry::STATUSES as $code => $label)<option value="{{ $code }}" @selected(old('status_code') === $code)>{{ $label }}</option>@endforeach</select></div>
                <div class="field"><label for="dental-note">یادداشت بالینی</label><textarea id="dental-note" name="note" rows="4" placeholder="توضیح یا علت ثبت وضعیت">{{ old('note') }}</textarea></div>
                <button class="button button--primary" type="submit">ثبت رویداد نمودار</button>
            </form>
        @else
            <p class="muted">شما مجوز ثبت یا اصلاح وضعیت نمودار دندان را ندارید.</p>
        @endif
    </aside>
</section>

<section class="card">
    <div class="section-heading"><div><span class="eyebrow">قابل پیگیری</span><h2>تاریخچهٔ نمودار دندان</h2></div></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>دندان</th><th>سطح</th><th>وضعیت</th><th>یادداشت</th><th>ثبت‌کننده</th><th>زمان</th></tr></thead>
            <tbody>
                @forelse ($history as $entry)
                    <tr><td dir="ltr"><bdi>{{ $entry->tooth_code }}</bdi></td><td dir="ltr"><bdi>{{ $entry->surface_code }}</bdi></td><td>{{ \App\Models\DentalChartEntry::STATUSES[$entry->status_code] ?? $entry->status_code }}</td><td>{{ $entry->note ?: '—' }}</td><td>{{ $entry->recorder?->name ?: 'کاربر سامانه' }}</td><td dir="ltr"><bdi>{{ $entry->created_at?->format('Y-m-d H:i') }}</bdi></td></tr>
                @empty
                    <tr><td colspan="6" class="empty-state">هنوز رویدادی در نمودار دندان ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
