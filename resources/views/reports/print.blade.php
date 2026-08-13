<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background: #fff; color: #172033; font-family: 'Vazirmatn', Tahoma, sans-serif; }
        .print-report { max-width: 1200px; margin: 2rem auto; padding: 0 1.25rem; }
        .print-report__head { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; border-bottom: 2px solid #d9e2ec; padding-bottom: 1rem; margin-bottom: 1rem; }
        .print-report__meta { color: #526174; font-size: .9rem; }
        .print-report__actions { display: flex; gap: .5rem; }
        @media print { .print-report { margin: 0; padding: 0; } .print-report__actions { display: none; } .data-table { font-size: 10px; } }
    </style>
</head>
<body onload="window.print()">
    <main class="print-report">
        <header class="print-report__head">
            <div>
                <span class="eyebrow">{{ $tenant->name }}</span>
                <h1>{{ $report['definition']['title'] }}</h1>
                <p class="print-report__meta">بازهٔ گزارش: {{ $report['filters']['from_input'] }} تا {{ $report['filters']['to_input'] }} — تولید در {{ $report['generatedAt']->format('Y/m/d H:i') }}</p>
            </div>
            <div class="print-report__actions">
                <button class="button button--primary" type="button" onclick="window.print()">چاپ</button>
                <button class="button button--ghost" type="button" onclick="window.close()">بستن</button>
            </div>
        </header>
        <section class="stats-grid" aria-label="خلاصهٔ گزارش">
            @foreach ($report['kpis'] as $label => $value)
                <article class="stat-card"><span>{{ $label }}</span><strong>{{ is_float($value) ? number_format($value, 0, '.', ',') : number_format((int) $value) }}</strong></article>
            @endforeach
        </section>
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
                            <td @if ($column['type'] === 'ltr' || $column['type'] === 'money') dir="ltr" @endif>{{ $column['type'] === 'money' ? number_format((float) $value, 0, '.', ',') : $value }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($report['definition']['columns']) }}">داده‌ای برای چاپ پیدا نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
