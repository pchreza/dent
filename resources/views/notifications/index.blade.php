@extends('layouts.app', ['title' => 'اعلان‌ها'])

@section('content')
<div class="page-header"><div><span class="eyebrow">{{ $tenant->name }}</span><h1>اعلان‌ها</h1><p class="muted">درخواست‌های QR، نوبت‌ها و رویدادهای مهم کلینیک در این بخش نمایش داده می‌شوند.</p></div></div>
<section class="card"><div class="notification-list">
@forelse ($notifications as $notification)
    <article class="notification-row {{ $notification->status === 'unread' ? 'notification-row--unread' : '' }}"><div><span class="status-badge {{ $notification->status === 'unread' ? 'status-badge--info' : 'status-badge--success' }}">{{ $notification->status === 'unread' ? 'خوانده‌نشده' : 'خوانده‌شده' }}</span><h2>{{ $notification->title }}</h2><p class="muted">{{ $notification->body }}</p><small class="muted" dir="ltr"><bdi>{{ $notification->created_at ? \App\Support\JalaliDate::format($notification->created_at).' · '.$notification->created_at->format('H:i') : '—' }}</bdi></small></div><div class="inline-actions">@if ($notification->action_url)<a class="button button--ghost button--small" href="{{ $notification->action_url }}">مشاهده اقدام</a>@endif @if ($notification->status === 'unread')<form method="post" action="{{ route('notifications.read', ['notificationId' => $notification->id]) }}">@csrf<button class="button button--primary button--small" type="submit">خوانده شد</button></form>@endif</div></article>
@empty
    <p class="empty-state">اعلانی برای نمایش وجود ندارد.</p>
@endforelse
</div>@if ($notifications->hasPages())<div class="pagination-wrap">{{ $notifications->links() }}</div>@endif</section>
@endsection
