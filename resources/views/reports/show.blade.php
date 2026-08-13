@extends('layouts.app')

@section('content')
    @php
        $code = $report['code'];
        $filters = $report['filters'];
        $statusOptions = match ($code) {
            'appointments' => ['scheduled' => 'برنامه‌ریزی‌شده', 'confirmed' => 'تأییدشده', 'arrived' => 'حاضرشده', 'in_treatment' => 'در حال درمان', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده', 'no_show' => 'عدم حضور'],
            'treatments' => ['draft' => 'پیش‌نویس', 'proposed' => 'پیشنهادشده', 'approved' => 'تأییدشده', 'in_progress' => 'در حال انجام', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده', 'rejected' => 'ردشده'],
            'finance' => ['issued' => 'صادرشده', 'open' => 'باز', 'partially_paid' => 'پرداخت ناقص', 'paid' => 'تسویه‌شده', 'overdue' => 'سررسیدگذشته', 'cancelled' => 'لغوشده', 'waived' => 'بخشوده‌شده', 'refunded' => 'مستردشده'],
            default => [],
        };
        $moneyKpis = ['مبلغ برآوردی', 'مبلغ صورتحساب', 'وصول‌شده', 'مانده', 'مبلغ خدمات'];
        $query = request()->query();
    @endphp

    <section class="page-header">
        <div>
            <a class="back-link" href="{{ route('reports.index') }}"><x-ui.icon name="chevron" size="15" /> بازگشت به مرکز گزارش‌ها</a>
            <span class="eyebrow">{{ $tenant->name }}</span>
            <h1>{{ $report['definition']['title'] }}</h1>
            <p class="page-subtitle">{{ $report['definition']['description'] }}</p>
        </div>
        <div class="page-header__actions">
            <a class="button button--ghost" href="{{ route('reports.print', ['report' => $code] + $query) }}" target="_blank" rel="noopener"><x-ui.icon name="invoice" size="17" /> چاپ</a>
            @if (! $report['tooManyRows'])
                <a class="button button--primary" href="{{ route('reports.export', ['report' => $code] + $query) }}"><x-ui.icon name="check" size="17" /> دریافت CSV</a>
            @endif
        </div>
    </section>

    @if ($report['tooManyRows'])
        <div class="alert alert--warning" role="status">تعداد ردیف‌های منطبق {{ number_format($report['totalRows']) }} مورد است. برای دریافت CSV، بازه یا فیلتر گزارش را محدود کنید؛ سقف خروجی ۵۰۰۰ ردیف است.</div>
    @endif

    <section class="table-card report-filters" aria-labelledby="report-filters-title">
        <div class="section-heading">
            <div>
                <span class="eyebrow">فیلترها</span>
                <h2 id="report-filters-title">محدودهٔ گزارش</h2>
            </div>
            <span class="muted">{{ $report['definition']['date_label'] }}</span>
        </div>
        <form class="report-filter-form" method="get" action="{{ route('reports.show', ['report' => $code]) }}">
            <label>
                <span>از تاریخ</span>
                <input type="text" name="from" value="{{ $filters['from_input'] }}" inputmode="numeric" placeholder="۱۴۰۵/۰۱/۰۱" dir="ltr">
            </label>
            <label>
                <span>تا تاریخ</span>
                <input type="text" name="to" value="{{ $filters['to_input'] }}" inputmode="numeric" placeholder="۱۴۰۵/۰۱/۳۱" dir="ltr">
            </label>
            @if ($statusOptions)
                <label>
                    <span>وضعیت</span>
                    <select name="status">
                        <option value="">همهٔ وضعیت‌ها</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if (in_array($code, ['appointments'], true))
                <label>
                    <span>شعبه</span>
                    <select name="branch_id">
                        <option value="">همهٔ شعبه‌ها</option>
                        @foreach ($options['branches'] as $branch)
                            <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>پزشک</span>
                    <select name="practitioner_id">
                        <option value="">همهٔ پزشکان</option>
                        @foreach ($options['practitioners'] as $practitioner)
                            <option value="{{ $practitioner->id }}" @selected($filters['practitioner_id'] === $practitioner->id)>{{ $practitioner->user?->name ?? 'پزشک' }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if (in_array($code, ['treatments', 'services'], true))
                <label>
                    <span>خدمت</span>
                    <select name="treatment_id">
                        <option value="">همهٔ خدمات</option>
                        @foreach ($options['treatments'] as $treatment)
                            <option value="{{ $treatment->id }}" @selected($filters['treatment_id'] === $treatment->id)>{{ $treatment->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if ($code === 'finance')
                <label>
                    <span>روش پرداخت</span>
                    <input type="text" name="method" value="{{ $filters['method'] ?? '' }}" placeholder="مثلاً کارت">
                </label>
            @endif
            <label class="report-filter-form__search">
                <span>جست‌وجو</span>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="نام، موبایل یا شمارهٔ بیمار">
            </label>
            <div class="report-filter-form__actions">
                <button class="button button--primary" type="submit"><x-ui.icon name="dashboard" size="17" /> اعمال فیلتر</button>
                <a class="button button--ghost" href="{{ route('reports.show', ['report' => $code]) }}">پاک‌سازی</a>
            </div>
        </form>
    </section>

    <section class="stats-grid report-kpis" aria-label="خلاصهٔ گزارش">
        @foreach ($report['kpis'] as $label => $value)
            <article class="stat-card">
                <span>{{ $label }}</span>
                <strong>{{ in_array($label, $moneyKpis, true) ? number_format((float) $value, 0, '.', ',') : number_format((int) $value) }}</strong>
                @if (in_array($label, $moneyKpis, true))<small>ریال</small>@endif
            </article>
        @endforeach
    </section>

    <section class="table-card" aria-labelledby="report-table-title">
        <div class="section-heading">
            <div>
                <span class="eyebrow">نتایج</span>
                <h2 id="report-table-title">{{ number_format($report['totalRows']) }} ردیف منطبق</h2>
            </div>
            <span class="muted">تولیدشده در {{ $report['generatedAt']->format('H:i') }}</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach ($report['definition']['columns'] as $column)
                            <th scope="col">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr>
                            @foreach ($report['definition']['columns'] as $column)
                                @php($value = $row[$column['key']] ?? '—')
                                <td>
                                    @if ($column['type'] === 'money')
                                        <span dir="ltr">{{ number_format((float) $value, 0, '.', ',') }}</span>
                                    @elseif ($column['type'] === 'status')
                                        <span class="badge">{{ $value }}</span>
                                    @elseif ($column['type'] === 'ltr')
                                        <bdi dir="ltr">{{ $value }}</bdi>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($report['definition']['columns']) }}"><div class="empty-state"><strong>در این بازه داده‌ای پیدا نشد.</strong><span>فیلترها را تغییر دهید یا بازهٔ زمانی دیگری انتخاب کنید.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
