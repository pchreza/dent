@extends('layouts.app', ['title' => 'نقشهٔ فک و روند درمان'])

@section('content')
@php
    $canEditDental = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'dentistry.update');
    $selectedSurface = request('surface', $selectedTooth['latest_entry']?->surface_code ?? 'all');
    $stepStatuses = ['planned' => 'برنامه‌ریزی', 'approved' => 'تأیید', 'in_progress' => 'در حال انجام', 'completed' => 'تکمیل'];
@endphp

<div class="page-header dental-workspace-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }} · پروندهٔ {{ $patient->patient_no }}</span>
        <h1>نقشهٔ فک و روند درمان</h1>
        <p class="muted">فقط دندان‌های دارای سابقه یا طرح درمان نمایش داده می‌شوند. برای دیدن جایگاه همهٔ دندان‌ها، حالت «نمایش همه» را انتخاب کنید.</p>
    </div>
    <div class="inline-actions">
        <a class="button button--secondary" href="{{ route('patients.show', ['patientId' => $patient->id]) }}">بازگشت به پرونده</a>
        <a class="button button--ghost" href="{{ route('patients.index') }}">فهرست بیماران</a>
    </div>
</div>

<section class="dental-kpi-strip" aria-label="خلاصهٔ وضعیت دهان و دندان">
    <article><span>دندان‌های فعال</span><strong dir="ltr"><bdi>{{ $activeCount }}</bdi></strong><small>دارای رخداد یا درمان</small></article>
    <article><span>دندان‌های پنهان</span><strong dir="ltr"><bdi>{{ $hiddenCount }}</bdi></strong><small>بدون رخداد ثبت‌شده</small></article>
    <article><span>رویدادهای بالینی</span><strong dir="ltr"><bdi>{{ $history->count() }}</bdi></strong><small>تاریخچهٔ قابل پیگیری</small></article>
</section>

<section class="dental-workspace" data-dental-workspace>
    <script type="application/json" data-dental-journeys>@json($journeyByTooth)</script>
    <article class="card dental-workspace__map-card">
        <div class="section-heading dental-workspace__heading">
            <div><span class="eyebrow">Odontogram · FDI</span><h2>تصویر بالینی فک</h2></div>
            <span class="status-badge status-badge--info">{{ $activeCount }} دندان فعال</span>
        </div>
        <x-dental-chart.jaw-map :teeth="$teeth" :active-codes="$activeCodes" :selected-code="$selectedCode" />
    </article>

    <aside class="card dental-journey-panel" id="tooth-journey" data-tooth-journey>
        @if ($selectedTooth)
            <header class="dental-journey-panel__header">
                <span class="eyebrow">داستان درمان دندان</span>
                <h2 data-journey-title>{{ $selectedTooth['display_name'] }}</h2>
                <div class="dental-journey-panel__identity"><span dir="ltr"><bdi data-journey-code>{{ $selectedTooth['fdi'] }}</bdi></span><span data-journey-status>{{ $selectedTooth['status_label'] }}</span></div>
            </header>

            <section class="journey-section" aria-labelledby="current-status-title">
                <div class="journey-section__title"><h3 id="current-status-title">وضعیت فعلی</h3><small>{{ $selectedTooth['latest_entry'] ? \App\Support\JalaliDate::format($selectedTooth['latest_entry']->created_at).' · '.$selectedTooth['latest_entry']->created_at->format('H:i') : 'هنوز ثبت بالینی ندارد' }}</small></div>
                <div class="journey-surface-chips" data-journey-surfaces>
                    @forelse ($selectedTooth['surfaces'] as $surface)
                        <button type="button" class="journey-surface-chip {{ $selectedSurface === $surface ? 'is-active' : '' }}" data-journey-surface="{{ $surface }}" data-surface-label="{{ \App\Support\DentalToothPresenter::surfaceLabel($surface) }}">{{ \App\Support\DentalToothPresenter::surfaceLabel($surface) }}</button>
                    @empty
                        <span class="journey-surface-chip">کل دندان</span>
                    @endforelse
                </div>
                @if ($selectedTooth['latest_entry']?->note)
                    <p class="journey-note" data-journey-note>{{ $selectedTooth['latest_entry']->note }}</p>
                @endif
            </section>

            <section class="journey-section" aria-labelledby="treatment-path-title">
                <div class="journey-section__title"><h3 id="treatment-path-title">مسیر درمان</h3><small>{{ count($journey['treatments']) }} آیتم متصل</small></div>
                <div data-journey-treatments>
                @forelse ($journey['treatments'] as $treatment)
                    <article class="treatment-path-card">
                        <div><strong>{{ $treatment['stage'] }}</strong><span>{{ $treatment['treatment'] }} · {{ $treatment['plan_title'] }}</span></div>
                        <ol class="treatment-stepper" aria-label="وضعیت آیتم درمان">
                            @foreach ($stepStatuses as $statusCode => $statusLabel)
                                @php
                                    $statusIndex = array_search($treatment['status'], array_keys($stepStatuses), true);
                                    $stepIndex = array_search($statusCode, array_keys($stepStatuses), true);
                                @endphp
                                <li class="{{ $treatment['status'] === 'cancelled' ? 'is-cancelled' : ($stepIndex <= $statusIndex ? 'is-complete' : '') }}"><span>{{ $statusLabel }}</span></li>
                            @endforeach
                        </ol>
                        <div class="treatment-path-card__meta"><span>{{ $treatment['status_label'] }}</span>@if ($treatment['planned_on'])<time dir="ltr"><bdi>{{ \App\Support\JalaliDate::format($treatment['planned_on']) }}</bdi></time>@endif</div>
                    </article>
                @empty
                    <div class="journey-empty-action">
                        <p>هنوز طرح درمانی برای این دندان ثبت نشده است.</p>
                        <a class="button button--secondary" data-treatment-create-link data-treatment-create-url="{{ route('treatment-plans.create', ['patientId' => $patient->id]) }}" href="{{ route('treatment-plans.create', ['patientId' => $patient->id, 'tooth' => $selectedTooth['code'], 'surface' => $selectedSurface]) }}">افزودن به طرح درمان</a>
                    </div>
                @endforelse
                </div>
            </section>

            <section class="journey-section" aria-labelledby="clinical-timeline-title">
                <div class="journey-section__title"><h3 id="clinical-timeline-title">روند بالینی</h3><small>{{ count($journey['timeline']) }} رخداد</small></div>
                <ol class="journey-timeline" data-journey-timeline>
                    @forelse ($journey['timeline'] as $event)
                        <li class="journey-timeline__event journey-timeline__event--{{ $event['type'] }}">
                            <div><strong>{{ $event['title'] }}</strong><span>{{ $event['type_label'] }} · {{ $event['subtitle'] }}</span>@if ($event['note'])<p>{{ $event['note'] }}</p>@endif</div>
                            <time dir="ltr"><bdi>{{ $event['at'] ? \App\Support\JalaliDate::format($event['at']).' · '.$event['at']->format('H:i') : '—' }}</bdi></time>
                        </li>
                    @empty
                        <li class="journey-timeline__empty">هنوز رویدادی برای این دندان ثبت نشده است.</li>
                    @endforelse
                </ol>
            </section>

            @if ($canEditDental)
                <section class="journey-section journey-section--quick-entry" aria-labelledby="quick-entry-title">
                    <div class="journey-section__title"><h3 id="quick-entry-title">ثبت سریع وضعیت</h3><small>تاریخچه محفوظ می‌ماند</small></div>
                    <form method="post" action="{{ route('dental-chart.store', ['patientId' => $patient->id]) }}" class="stack-form" data-dental-quick-entry>
                        @csrf
                        <input type="hidden" name="tooth_code" value="{{ $selectedTooth['code'] }}" data-dental-tooth-input>
                        <input type="hidden" name="surface_code" value="{{ $selectedSurface }}" data-dental-surface-input>
                        <div class="field"><label for="status-code" data-dental-entry-label>وضعیت جدید برای {{ $selectedTooth['short_name'] }}</label><select id="status-code" name="status_code" required><option value="">انتخاب وضعیت</option>@foreach (\App\Models\DentalChartEntry::STATUSES as $code => $label)<option value="{{ $code }}" @selected(old('status_code') === $code)>{{ $label }}</option>@endforeach</select></div>
                        <div class="field"><label for="dental-note">یادداشت بالینی</label><textarea id="dental-note" name="note" rows="3" placeholder="توضیح یا علت ثبت وضعیت">{{ old('note') }}</textarea></div>
                        <button class="button button--primary" type="submit">ثبت رویداد جدید</button>
                    </form>
                </section>
            @endif
        @else
            <div class="dental-journey-panel__empty">
                <span class="eyebrow">داستان درمان دندان</span>
                <h2>یک دندان فعال انتخاب کنید</h2>
                <p>پس از ثبت یک رویداد یا ایجاد طرح درمان، روند بالینی و اجرایی آن دندان در اینجا نمایش داده می‌شود.</p>
            </div>
        @endif
    </aside>
</section>

<section class="card dental-history-card">
    <div class="section-heading"><div><span class="eyebrow">سوابق کامل</span><h2>تاریخچهٔ نمودار دندان</h2></div><span class="muted">برای دسترسی کامل، تاریخچهٔ متنی حفظ شده است.</span></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>دندان</th><th>سطح</th><th>وضعیت</th><th>یادداشت</th><th>ثبت‌کننده</th><th>زمان</th></tr></thead>
            <tbody>
                @forelse ($history as $entry)
                    <tr><td dir="ltr"><bdi>{{ $entry->tooth_code }}</bdi></td><td>{{ \App\Support\DentalToothPresenter::surfaceLabel($entry->surface_code) }}</td><td>{{ \App\Models\DentalChartEntry::STATUSES[$entry->status_code] ?? $entry->status_code }}</td><td>{{ $entry->note ?: '—' }}</td><td>{{ $entry->recorder?->name ?: 'کاربر سامانه' }}</td><td dir="ltr"><bdi>{{ $entry->created_at ? \App\Support\JalaliDate::format($entry->created_at).' · '.$entry->created_at->format('H:i') : '—' }}</bdi></td></tr>
                @empty
                    <tr><td colspan="6" class="empty-state">هنوز رویدادی در نمودار دندان ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
