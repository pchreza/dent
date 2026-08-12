@extends('layouts.patient')

@section('content')
    <header class="page-header">
        <div>
            <span class="eyebrow">حساب کاربری</span>
            <h1>اعلان‌های من</h1>
            <p>پیام‌های مرتبط با پروندهٔ شما در کلینیک فعال.</p>
        </div>
    </header>

    <section class="notification-list">
        @forelse ($notifications as $notification)
            <article class="card notification-card {{ $notification->status === 'unread' ? 'notification-card--unread' : '' }}">
                <div>
                    <span class="badge {{ $notification->status === 'unread' ? 'badge--info' : '' }}">{{ $notification->status === 'unread' ? 'خوانده‌نشده' : 'خوانده‌شده' }}</span>
                    <h2>{{ $notification->title }}</h2>
                    <p>{{ $notification->body }}</p>
                </div>
                <time datetime="{{ $notification->created_at->toDateString() }}"><bdi dir="ltr">{{ \App\Support\JalaliDate::format($notification->created_at) }}</bdi></time>
            </article>
        @empty
            <div class="card empty-state">
                <strong>اعلانی برای نمایش وجود ندارد.</strong>
                <p>پیام‌های مرتبط با پروندهٔ شما در این بخش نمایش داده خواهند شد.</p>
            </div>
        @endforelse
    </section>

    <div class="pagination-wrap">{{ $notifications->links() }}</div>
@endsection
