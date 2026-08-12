@extends('layouts.app', ['title' => 'شعبه‌های کلینیک'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }}</span>
        <h1>شعبه‌های کلینیک</h1>
        <p class="muted">ساختار شعبه‌ها مستقل از سایر Tenantها نگهداری می‌شود.</p>
    </div>
    <a class="button button--primary" href="{{ route('branches.create') }}">افزودن شعبه</a>
</div>

<section class="card">
    <div class="table-wrap">
        <table class="data-table">
            <caption class="sr-only">فهرست شعبه‌ها</caption>
            <thead>
                <tr><th scope="col">نام</th><th scope="col">کد</th><th scope="col">تلفن</th><th scope="col">وضعیت</th></tr>
            </thead>
            <tbody>
                @forelse ($branches as $branch)
                    <tr>
                        <td><strong>{{ $branch->name }}</strong></td>
                        <td dir="ltr"><bdi>{{ $branch->code }}</bdi></td>
                        <td dir="ltr"><bdi>{{ $branch->phone ?: '—' }}</bdi></td>
                        <td><span class="status-badge {{ $branch->is_active ? 'status-badge--success' : 'status-badge--danger' }}">{{ $branch->is_active ? 'فعال' : 'غیرفعال' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-state">هنوز شعبه‌ای ساخته نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($branches->hasPages())
        <div class="pagination-wrap">{{ $branches->links() }}</div>
    @endif
</section>
@endsection
