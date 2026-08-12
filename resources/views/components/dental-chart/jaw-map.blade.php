@props(['teeth', 'activeCodes', 'selectedCode'])

@php
    $showAll = request()->boolean('all');
    $dentition = request('dentition', $showAll ? 'permanent' : 'all');
    $visibleTeeth = $teeth->filter(static function (array $tooth) use ($showAll, $dentition): bool {
        if ($dentition === 'permanent' && $tooth['is_primary']) {
            return false;
        }
        if ($dentition === 'primary' && ! $tooth['is_primary']) {
            return false;
        }

        return $showAll || $tooth['is_active'];
    });
    $visibleStatus = $showAll ? 'نمایش همهٔ دندان‌ها' : 'فقط دندان‌های دارای وضعیت';
@endphp

<div class="jaw-map" data-jaw-map data-show-all="{{ $showAll ? 'true' : 'false' }}">
    <svg class="jaw-symbol-library" aria-hidden="true" focusable="false">
        <defs>
            <symbol id="jaw-tooth-incisor" viewBox="0 0 32 42"><path d="M7 5c2.5-3 15.5-3 18 0 2.8 3.7 1.4 12-1.2 17.8-2.2 4.8-2.1 13.8-5.6 13.8-1.5 0-1.4-7.2-2.2-7.2s-.7 7.2-2.2 7.2c-3.5 0-3.4-9-5.6-13.8C5.6 17 4.2 8.7 7 5Z" /></symbol>
            <symbol id="jaw-tooth-canine" viewBox="0 0 32 42"><path d="M8 5c3-3.6 13-3.6 16 0 3 3.7 2.4 11.4-1.5 17.3-2.8 4.2-3 14.7-6.5 14.7s-3.7-10.5-6.5-14.7C5.6 16.4 5 8.7 8 5Z" /></symbol>
            <symbol id="jaw-tooth-premolar" viewBox="0 0 38 42"><path d="M8 5c3.2-3.5 18.8-3.5 22 0 3.3 3.6 2.3 12.5-1.6 17.5-3.1 3.9-2.9 14.8-7.2 14.8-1.7 0-1.4-8.2-2.2-8.2s-.5 8.2-2.2 8.2c-4.3 0-4.1-10.9-7.2-14.8C5.7 17.5 4.7 8.6 8 5Z" /></symbol>
            <symbol id="jaw-tooth-molar" viewBox="0 0 44 42"><path d="M7 5c3.7-3.7 26.3-3.7 30 0 3.6 3.7 1.2 13.5-2.5 18.2-3.6 4.5-3.5 13.8-8.1 13.8-2 0-1.4-8.1-4.3-8.1s-2.3 8.1-4.3 8.1c-4.6 0-4.5-9.3-8.1-13.8C5.8 18.5 3.4 8.7 7 5Z" /></symbol>
        </defs>
    </svg>

    <div class="jaw-map__toolbar">
        <div>
            <span class="eyebrow">نمای بالینی</span>
            <strong>{{ $visibleStatus }}</strong>
            <small>{{ $visibleTeeth->count() }} دندان نمایش داده می‌شود</small>
        </div>
        <div class="jaw-map__controls" aria-label="کنترل نمایش نمودار">
            <a class="segmented-control {{ ! $showAll ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['all' => 0, 'dentition' => 'all']) }}">فقط دندان‌های فعال</a>
            <a class="segmented-control {{ $showAll && $dentition === 'permanent' ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['all' => 1, 'dentition' => 'permanent']) }}">همهٔ دندان‌های دائمی</a>
            <a class="segmented-control {{ $showAll && $dentition === 'primary' ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['all' => 1, 'dentition' => 'primary']) }}">همهٔ دندان‌های شیری</a>
        </div>
    </div>

    @if ($visibleTeeth->isEmpty())
        <div class="jaw-map__empty">
            <strong>هنوز برای این نما دندان فعالی وجود ندارد.</strong>
            <p>برای مشاهدهٔ جایگاه تمام دندان‌ها، نمای کامل را باز کنید یا اولین وضعیت بالینی را ثبت نمایید.</p>
            <a class="button button--secondary" href="{{ request()->fullUrlWithQuery(['all' => 1]) }}">نمایش همهٔ دندان‌ها</a>
        </div>
    @else
        <div class="jaw-scene" aria-label="تصویر گرافیکی فک و دندان‌ها">
            <svg class="jaw-scene__anatomy" viewBox="0 0 100 100" role="img" aria-label="تصویر فک بالا و پایین">
                <defs>
                    <linearGradient id="jaw-bone" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#fbf4ed" /><stop offset="1" stop-color="#e7d7c9" /></linearGradient>
                    <linearGradient id="jaw-gum" x1="0" x2="0" y1="0" y2="1"><stop stop-color="#f3b7ba" /><stop offset="1" stop-color="#d98791" /></linearGradient>
                    <filter id="jaw-shadow" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="1.5" stdDeviation="1.2" flood-color="#4a2c2f" flood-opacity=".2" /></filter>
                </defs>
                <path class="jaw-scene__bone jaw-scene__bone--upper" d="M8 44C9 17 28 7 50 7s41 10 42 37c-8-12-22-20-42-20S16 32 8 44Z" fill="url(#jaw-bone)" />
                <path class="jaw-scene__bone jaw-scene__bone--lower" d="M8 56c1 27 20 37 42 37s41-10 42-37c-8 12-22 20-42 20S16 68 8 56Z" fill="url(#jaw-bone)" />
                <path class="jaw-scene__gum jaw-scene__gum--upper" d="M12 42c5-16 19-24 38-24s33 8 38 24c-10-8-23-12-38-12S22 34 12 42Z" fill="url(#jaw-gum)" filter="url(#jaw-shadow)" />
                <path class="jaw-scene__gum jaw-scene__gum--lower" d="M12 58c5 16 19 24 38 24s33-8 38-24c-10 8-23 12-38 12S22 66 12 58Z" fill="url(#jaw-gum)" filter="url(#jaw-shadow)" />
                <path class="jaw-scene__midline" d="M50 18v15M50 67v15" />
                <path class="jaw-scene__arc" d="M15 41C27 25 39 22 50 22s23 3 35 19M15 59c12 16 24 19 35 19s23-3 35-19" />
                <text x="50" y="12" text-anchor="middle">فک بالا</text>
                <text x="50" y="91" text-anchor="middle">فک پایین</text>
            </svg>

            <div class="jaw-scene__teeth" aria-live="polite">
                @foreach ($visibleTeeth as $tooth)
                    @php
                        $placement = $tooth['placement'];
                        $status = $tooth['status_code'] ?? ($tooth['is_active'] ? 'monitor' : 'healthy');
                        $isSelected = $selectedCode === $tooth['code'];
                        $surfaceCodes = array_values(array_filter($tooth['surfaces'], static fn (string $surface): bool => $surface !== 'all'));
                    @endphp
                    <button
                        type="button"
                        class="jaw-tooth jaw-tooth--{{ $placement['family'] }} jaw-tooth--{{ $status }} {{ $isSelected ? 'is-selected' : '' }} {{ $tooth['is_primary'] ? 'jaw-tooth--primary' : '' }}"
                        data-jaw-tooth
                        data-tooth-code="{{ $tooth['code'] }}"
                        data-tooth-name="{{ $tooth['display_name'] }}"
                        data-tooth-status="{{ $tooth['status_label'] }}"
                        data-tooth-surface="{{ $tooth['latest_entry']?->surface_code ?? 'all' }}"
                        aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                        aria-label="{{ $tooth['display_name'] }}، {{ $tooth['fdi'] }}، وضعیت {{ $tooth['status_label'] }}"
                        style="--tooth-x: {{ $placement['x'] }}%; --tooth-y: {{ $placement['y'] }}%; --tooth-rotation: {{ $placement['rotation'] }}deg;"
                    >
                        <span class="jaw-tooth__shape" aria-hidden="true"><svg viewBox="0 0 44 42"><use href="#jaw-tooth-{{ $placement['family'] }}"></use></svg></span>
                        @foreach ($surfaceCodes as $surface)
                            <span class="jaw-tooth__surface jaw-tooth__surface--{{ strtolower($surface) }}" aria-hidden="true"></span>
                        @endforeach
                        @if ($isSelected || $tooth['is_active'])
                            <span class="jaw-tooth__code" dir="ltr"><bdi>{{ $tooth['code'] }}</bdi></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div class="jaw-map__legend" aria-label="راهنمای وضعیت‌های بالینی">
        @foreach (['caries' => 'پوسیدگی', 'root_canal_needed' => 'عصب‌کشی', 'restored' => 'ترمیم/روکش', 'implant' => 'ایمپلنت', 'missing' => 'مفقود', 'monitor' => 'پیگیری'] as $code => $label)
            <span><i class="jaw-map__legend-mark jaw-map__legend-mark--{{ $code }}" aria-hidden="true"></i>{{ $label }}</span>
        @endforeach
    </div>
</div>
