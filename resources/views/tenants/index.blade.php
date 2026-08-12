@extends('layouts.app', ['title' => 'مدیریت کلینیک‌ها'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">پنل سوپرادمین</span>
        <h1>کلینیک‌ها</h1>
        <p class="muted">هر کلینیک یک Tenant مستقل با داده و تنظیمات جدا است.</p>
    </div>
    <a class="button button--primary" href="{{ route('tenants.create') }}">افزودن کلینیک</a>
</div>

<section class="card">
    <div class="table-wrap">
        <table class="data-table">
            <caption class="sr-only">فهرست کلینیک‌ها</caption>
            <thead>
                <tr>
                    <th scope="col">نام</th>
                    <th scope="col"><span dir="ltr">Code</span></th>
                    <th scope="col">وضعیت</th>
                    <th scope="col">پلن</th>
                    <th scope="col">کاربران</th>
                    <th scope="col">فرم QR</th>
                    <th scope="col">تاریخ پایان</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tenants as $tenant)
                    <tr>
                        <td><strong>{{ $tenant->name }}</strong></td>
                        <td dir="ltr"><bdi>{{ $tenant->code }}</bdi></td>
                        <td><span class="status-badge status-badge--info">{{ ['active' => 'فعال', 'trial' => 'آزمایشی', 'suspended' => 'معلق', 'inactive' => 'غیرفعال'][$tenant->status] ?? $tenant->status }}</span></td>
                        <td dir="ltr"><bdi>{{ $tenant->plan_code }}</bdi></td>
                        <td>{{ number_format($tenant->users_count) }}</td>
                        <td><a class="button button--ghost button--small" href="{{ $tenant->qrRegistrationUrl() }}" target="_blank" rel="noreferrer">بازکردن فرم</a></td>
                        <td dir="ltr"><bdi>{{ $tenant->ends_on ? \App\Support\JalaliDate::format($tenant->ends_on) : '—' }}</bdi></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">هنوز کلینیکی ساخته نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($tenants->hasPages())
        <div class="pagination-wrap">{{ $tenants->links() }}</div>
    @endif
</section>
@endsection
