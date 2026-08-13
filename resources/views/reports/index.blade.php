@extends('layouts.app')

@section('content')
    <section class="page-header">
        <div>
            <span class="eyebrow">{{ $tenant->name }}</span>
            <h1>مرکز گزارش‌ها</h1>
            <p class="page-subtitle">گزارش‌های عملیاتی کلینیک را با فیلتر شمسی مشاهده، چاپ یا به‌صورت CSV دریافت کنید.</p>
        </div>
    </section>

    <section class="stats-grid" aria-label="گزارش‌های قابل دسترس">
        @forelse ($reports as $code => $report)
            <a class="table-card report-card" href="{{ route('reports.show', ['report' => $code]) }}">
                <div class="report-card__icon"><x-ui.icon name="dashboard" size="22" /></div>
                <div>
                    <h2>{{ $report['title'] }}</h2>
                    <p>{{ $report['description'] }}</p>
                    <span class="button button--ghost button--small">مشاهده گزارش <x-ui.icon name="chevron" size="15" /></span>
                </div>
            </a>
        @empty
            <div class="empty-state">
                <strong>گزارشی برای حساب شما فعال نیست.</strong>
                <span>از مدیر کلینیک بخواهید مجوز گزارش یا مجوز ماژول مربوطه را فعال کند.</span>
            </div>
        @endforelse
    </section>
@endsection
