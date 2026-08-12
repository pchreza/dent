@extends('layouts.patient')

@section('content')
    <header class="page-header">
        <div>
            <span class="eyebrow">برنامه‌ریزی</span>
            <h1>نوبت‌های من</h1>
            <p>فقط نوبت‌های ثبت‌شده برای پروندهٔ شما در کلینیک فعال نمایش داده می‌شوند.</p>
        </div>
    </header>

    <section class="card table-card">
        @if ($appointments->isEmpty())
            <div class="empty-state">
                <strong>نوبتی برای نمایش وجود ندارد.</strong>
                <p>برای ثبت یا تغییر نوبت با کلینیک تماس بگیرید.</p>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>عنوان</th>
                            <th>تاریخ و ساعت</th>
                            <th>شعبه</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($appointments as $appointment)
                            <tr>
                                <td>
                                    <strong>{{ $appointment->title }}</strong>
                                    @if ($appointment->appointment_type)<small>{{ $appointment->appointment_type }}</small>@endif
                                </td>
                                <td><bdi dir="ltr">{{ \App\Support\JalaliDate::format($appointment->starts_at) }} · {{ $appointment->starts_at->format('H:i') }}</bdi></td>
                                <td>{{ $appointment->branch?->name ?? 'تعیین نشده' }}</td>
                                <td><span class="badge badge--info">{{ match($appointment->status) {
                                    'confirmed' => 'تأییدشده',
                                    'arrived' => 'پذیرش‌شده',
                                    'in_treatment' => 'در حال درمان',
                                    'completed' => 'تکمیل‌شده',
                                    'cancelled_by_patient' => 'لغو توسط بیمار',
                                    'cancelled_by_clinic' => 'لغو توسط کلینیک',
                                    'no_show' => 'عدم مراجعه',
                                    'rescheduled' => 'جابجا شده',
                                    default => 'در انتظار تأیید',
                                } }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $appointments->links() }}</div>
        @endif
    </section>
@endsection
