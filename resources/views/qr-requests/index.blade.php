@extends('layouts.app', ['title' => 'درخواست‌های ثبت‌نام'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }}</span>
        <h1>درخواست‌های ثبت‌نام</h1>
        <p class="muted">درخواست‌های ثبت‌نام‌شده از QR را قبل از ساخت پرونده بررسی کنید.</p>
    </div>
</div>

<section class="card">
    <div class="table-wrap">
        <table class="data-table">
            <caption class="sr-only">صف درخواست‌های ثبت‌نام QR</caption>
            <thead><tr><th>زمان</th><th>نام</th><th>موبایل</th><th>هشدار</th><th>وضعیت</th><th>اقدام</th></tr></thead>
            <tbody>
            @forelse ($requests as $registrationRequest)
                @php($payload = $registrationRequest->payload)
                <tr>
                    <td dir="ltr"><bdi>{{ $registrationRequest->created_at?->format('Y-m-d H:i') }}</bdi></td>
                    <td><strong>{{ $payload['first_name'] ?? '—' }} {{ $payload['last_name'] ?? '' }}</strong></td>
                    <td dir="ltr"><bdi>{{ $payload['mobile'] ?? '—' }}</bdi></td>
                    <td>
                        @if (count($registrationRequest->duplicate_match ?? []) > 0)
                            <span class="status-badge status-badge--danger">تطبیق احتمالی</span>
                        @else
                            <span class="status-badge status-badge--success">بدون تطبیق</span>
                        @endif
                    </td>
                    <td><span class="status-badge status-badge--info">{{ $registrationRequest->status }}</span></td>
                    <td>
                        @if ($registrationRequest->status === 'pending')
                            <div class="inline-actions">
                                <form method="post" action="{{ route('qr-requests.approve', ['registrationRequestId' => $registrationRequest->id]) }}">@csrf<button class="button button--primary button--small" type="submit">تأیید</button></form>
                                <form method="post" action="{{ route('qr-requests.reject', ['registrationRequestId' => $registrationRequest->id]) }}" class="reject-form">@csrf<input name="reason" aria-label="دلیل رد" placeholder="دلیل رد" required minlength="3"><button class="button button--danger button--small" type="submit">رد</button></form>
                            </div>
                        @else
                            <span class="muted">بررسی‌شده</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">درخواست جدیدی وجود ندارد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($requests->hasPages())<div class="pagination-wrap">{{ $requests->links() }}</div>@endif
</section>
@endsection
