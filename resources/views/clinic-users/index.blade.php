@extends('layouts.app', ['title' => 'کاربران کلینیک'])

@section('content')
<div class="page-header"><div><span class="eyebrow">{{ $tenant->name }}</span><h1>کاربران کلینیک</h1><p class="muted">مدیر کلینیک می‌تواند پزشک و منشی اضافه و سطح نقش آن‌ها را تعیین کند.</p></div><a class="button button--primary" href="{{ route('clinic-users.create') }}">افزودن پزشک/منشی</a></div>
<section class="card"><div class="table-wrap"><table class="data-table"><thead><tr><th>نام</th><th>موبایل</th><th>نام کاربری</th><th>نقش</th><th>وضعیت</th></tr></thead><tbody>
@forelse ($users as $user)
    <tr><td><strong>{{ $user->name }}</strong></td><td dir="ltr"><bdi>{{ $user->mobile }}</bdi></td><td dir="ltr"><bdi>{{ $user->username }}</bdi></td><td>{{ $user->roles->first()?->name ?: 'بدون نقش' }}</td><td><span class="status-badge status-badge--success">فعال</span></td></tr>
@empty
    <tr><td colspan="5" class="empty-state">کاربر کلینیکی وجود ندارد.</td></tr>
@endforelse
</tbody></table></div>@if ($users->hasPages())<div class="pagination-wrap">{{ $users->links() }}</div>@endif</section>
@endsection
