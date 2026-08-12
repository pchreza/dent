@extends('layouts.app', ['title' => 'نمودار دندان'])

@section('content')
@php
    $canEditDental = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'dentistry.update');
    $latestByTooth = $currentEntries
        ->groupBy('tooth_code')
        ->map(static fn ($entries) => $entries->firstWhere('surface_code', 'all') ?? $entries->first());
    $statusCounts = $latestByTooth->pluck('status_code')->countBy();
    $attentionCount = $latestByTooth->filter(static fn ($entry): bool => $entry->status_code !== 'healthy')->count();
    $arches = [
        ['title' => 'فک بالا · دائمی', 'class' => 'jaw-arch--upper', 'teeth' => array_slice(\App\Models\DentalChartEntry::PERMANENT_TEETH, 0, 16)],
        ['title' => 'فک پایین · دائمی', 'class' => 'jaw-arch--lower', 'teeth' => array_slice(\App\Models\DentalChartEntry::PERMANENT_TEETH, 16)],
        ['title' => 'فک بالا · شیری', 'class' => 'jaw-arch--upper jaw-arch--primary', 'teeth' => array_slice(\App\Models\DentalChartEntry::PRIMARY_TEETH, 0, 10)],
        ['title' => 'فک پایین · شیری', 'class' => 'jaw-arch--lower jaw-arch--primary', 'teeth' => array_slice(\App\Models\DentalChartEntry::PRIMARY_TEETH, 10)],
    ];
@endphp

<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }} · پروندهٔ {{ $patient->patient_no }}</span>
        <h1>نقشهٔ گرافیکی دندان {{ $patient->fullName() }}</h1>
        <p class="muted">هر دندان را لمس یا انتخاب کنید تا وضعیت آن ببینید و یک رویداد بالینی جدید ثبت کنید؛ سابقهٔ قبلی محفوظ می‌ماند.</p>
    </div>
    <div class="inline-actions">
        <a class="button button--secondary" href="{{ route('patients.show', ['patientId' => $patient->id]) }}">بازگشت به پرونده</a>
        <a class="button button--ghost" href="{{ route('patients.index') }}">فهرست بیماران</a>
    </div>
</div>

<section class="clinical-overview-grid" aria-label="خلاصهٔ نمودار دندان">
    <article class="clinical-stat clinical-stat--total"><span>رویدادهای ثبت‌شده</span><strong dir="ltr"><bdi>{{ $history->count() }}</bdi></strong><small>تمام تغییرها قابل پیگیری‌اند</small></article>
    <article class="clinical-stat clinical-stat--attention"><span>نیازمند توجه</span><strong dir="ltr"><bdi>{{ $attentionCount }}</bdi></strong><small>آخرین وضعیت غیر سالم</small></article>
    <article class="clinical-stat clinical-stat--healthy"><span>دندان‌های سالم</span><strong dir="ltr"><bdi>{{ $statusCounts->get('healthy', 0) }}</bdi></strong><small>دارای ثبت وضعیت سالم</small></article>
</section>

<section class="dental-chart-layout dental-chart-layout--graphic">
    <article class="card dental-chart-card dental-chart-card--graphic" aria-labelledby="dental-chart-title">
        <div class="section-heading">
            <div><span class="eyebrow">Dental map · FDI</span><h2 id="dental-chart-title">نقشهٔ وضعیت فعلی دندان‌ها</h2></div>
            <span class="status-badge status-badge--info">انتخاب تعاملی</span>
        </div>
        <p class="muted dental-chart-help">کد FDI هر دندان زیر شکل آن نمایش داده شده است. رنگ آیکون، آخرین وضعیت ثبت‌شده را نشان می‌دهد و برای خوانایی، تنها دندان‌های دارای رویداد در آمار بالا شمرده می‌شوند.</p>

        <div class="dental-legend" aria-label="راهنمای رنگ وضعیت‌ها">
            @foreach ([
                'healthy' => 'سالم', 'caries' => 'پوسیدگی', 'restored' => 'ترمیم‌شده', 'root_canal_needed' => 'عصب‌کشی',
                'crown_needed' => 'روکش', 'missing' => 'مفقود', 'implant' => 'ایمپلنت', 'extracted' => 'کشیده‌شده', 'monitor' => 'پیگیری',
            ] as $statusCode => $statusLabel)
                <span class="dental-legend__item"><i class="dental-legend__mark dental-legend__mark--{{ $statusCode }}" aria-hidden="true"></i>{{ $statusLabel }}</span>
            @endforeach
        </div>

        <div class="dental-selection-banner" data-dental-selection-preview aria-live="polite">
            <span class="dental-selection-banner__tooth" data-dental-selected-tooth dir="ltr"><bdi>—</bdi></span>
            <div><strong data-dental-selected-title>یک دندان را از نقشه انتخاب کنید</strong><small data-dental-selected-status>کد و وضعیت آن برای ثبت در فرم آماده می‌شود.</small></div>
        </div>

        <div class="jaw-map" aria-label="نقشهٔ گرافیکی فک و دندان‌ها">
            @foreach ($arches as $arch)
                <section class="jaw-arch {{ $arch['class'] }}" aria-label="{{ $arch['title'] }}">
                    <div class="jaw-arch__header"><span>{{ $arch['title'] }}</span><i aria-hidden="true"></i></div>
                    <div class="jaw-arch__teeth">
                        @foreach ($arch['teeth'] as $toothCode)
                            @php
                                $entry = $latestByTooth->get($toothCode);
                                $status = $entry?->status_code ?? 'healthy';
                                $statusLabel = $entry ? (\App\Models\DentalChartEntry::STATUSES[$status] ?? 'ثبت نشده') : 'ثبت نشده';
                            @endphp
                            <button
                                type="button"
                                class="tooth-button tooth-button--{{ $status }}"
                                data-dental-tooth="{{ $toothCode }}"
                                data-dental-status="{{ $status }}"
                                data-dental-status-label="{{ $statusLabel }}"
                                aria-pressed="false"
                                aria-label="دندان {{ $toothCode }}، وضعیت {{ $statusLabel }}؛ برای انتخاب و ثبت رویداد کلیک کنید"
                            >
                                <span class="tooth-button__visual" aria-hidden="true">
                                    <svg viewBox="0 0 80 92" role="presentation" focusable="false">
                                        <path d="M20 10c8-6 14 2 20 2s12-8 20-2c9 7 7 24 3 36-3 10-6 30-15 30-5 0-5-18-8-18s-3 18-8 18c-9 0-12-20-15-30-4-12-6-29 3-36Z" />
                                    </svg>
                                    <span class="tooth-button__indicator"></span>
                                </span>
                                <span class="tooth-button__code" dir="ltr"><bdi>{{ $toothCode }}</bdi></span>
                                <span class="tooth-button__status">{{ $statusLabel }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </article>

    <aside class="card dental-entry-card dental-entry-card--guided" id="chart-entry">
        <div class="section-heading"><div><span class="eyebrow">ثبت افزایشی</span><h2>ثبت وضعیت دندان</h2></div></div>
        <p class="dental-entry-card__hint">ابتدا یک دندان را از نقشه انتخاب کنید، سپس وضعیت بالینی و یادداشت را ثبت نمایید.</p>
        @if ($canEditDental)
            <form method="post" action="{{ route('dental-chart.store', ['patientId' => $patient->id]) }}" class="stack-form">
                @csrf
                <div class="field"><label for="tooth-code">کد دندان</label>
                    <select id="tooth-code" name="tooth_code" data-dental-tooth-input required dir="ltr">
                        <option value="">انتخاب دندان</option>
                        <optgroup label="دندان‌های دائمی">@foreach (\App\Models\DentalChartEntry::PERMANENT_TEETH as $toothCode)<option value="{{ $toothCode }}" @selected(old('tooth_code') === $toothCode)>{{ $toothCode }}</option>@endforeach</optgroup>
                        <optgroup label="دندان‌های شیری">@foreach (\App\Models\DentalChartEntry::PRIMARY_TEETH as $toothCode)<option value="{{ $toothCode }}" @selected(old('tooth_code') === $toothCode)>{{ $toothCode }}</option>@endforeach</optgroup>
                    </select>
                </div>
                <div class="field"><label for="surface-code">سطح دندان</label><select id="surface-code" name="surface_code" required><option value="all">کل دندان</option>@foreach (array_slice(\App\Models\DentalChartEntry::SURFACES, 1) as $surface)<option value="{{ $surface }}" @selected(old('surface_code') === $surface) dir="ltr">{{ $surface }}</option>@endforeach</select></div>
                <div class="field"><label for="status-code">وضعیت بالینی</label><select id="status-code" name="status_code" required><option value="">انتخاب وضعیت</option>@foreach (\App\Models\DentalChartEntry::STATUSES as $code => $label)<option value="{{ $code }}" @selected(old('status_code') === $code)>{{ $label }}</option>@endforeach</select></div>
                <div class="field"><label for="dental-note">یادداشت بالینی</label><textarea id="dental-note" name="note" rows="4" placeholder="توضیح، محل یا علت ثبت وضعیت">{{ old('note') }}</textarea></div>
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
