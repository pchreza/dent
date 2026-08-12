@extends('layouts.app', ['title' => 'تقویم نوبت‌ها'])

@section('content')
@php($appointmentStatusLabels = ['scheduled' => 'برنامه‌ریزی‌شده', 'confirmed' => 'تأییدشده', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده'])
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }}</span>
        <h1>تقویم هفتگی نوبت‌ها</h1>
        <p class="muted">هفتهٔ کاری از شنبه تا جمعه؛ تاریخ‌ها بر اساس تقویم شمسی نمایش داده می‌شوند.</p>
    </div>
    <a class="button button--primary" href="{{ route('appointments.create') }}">ثبت نوبت</a>
</div>

<div class="calendar-toolbar">
    <a class="button button--ghost" href="{{ route('calendar.index', ['week' => $previousWeek]) }}">هفتهٔ قبل</a>
    <span class="calendar-toolbar__range">{{ $week[0]['jalali'] }} تا {{ $week[6]['jalali'] }}</span>
    <a class="button button--ghost" href="{{ route('calendar.index', ['week' => $nextWeek]) }}">هفتهٔ بعد</a>
</div>

<section class="weekly-calendar" aria-label="تقویم هفتگی">
    @foreach ($week as $day)
        @php($dayKey = $day['date']->format('Y-m-d'))
        <article class="calendar-day {{ $day['date']->isToday() ? 'calendar-day--today' : '' }}">
            <header class="calendar-day__header">
                <strong>{{ $day['weekday'] }}</strong>
                <span dir="ltr"><bdi>{{ $day['jalali'] }}</bdi></span>
            </header>
            <div class="calendar-day__appointments">
                @forelse ($appointments->get($dayKey, collect()) as $appointment)
                    <div class="appointment-card appointment-card--{{ $appointment->status }}">
                        <span class="appointment-card__time" dir="ltr"><bdi>{{ $appointment->starts_at->format('H:i') }}–{{ $appointment->ends_at->format('H:i') }}</bdi></span>
                        <strong>{{ $appointment->patient->fullName() }}</strong>
                        <small>{{ $appointment->title }}</small>
                        <small>{{ $appointment->practitioner?->user?->name ?: 'پزشک تعیین نشده' }}</small>
                        <span class="status-badge status-badge--info">{{ $appointmentStatusLabels[$appointment->status] ?? $appointment->status }}</span>
                    </div>
                @empty
                    <p class="calendar-day__empty">بدون نوبت</p>
                @endforelse
            </div>
        </article>
    @endforeach
</section>
@endsection
