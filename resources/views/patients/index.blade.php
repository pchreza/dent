@extends('layouts.app', ['title' => 'بیماران'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }}</span>
        <h1>بیماران</h1>
        <p class="muted">جست‌وجوی پرونده‌ها فقط در محدودهٔ کلینیک فعال انجام می‌شود.</p>
    </div>
</div>

<section class="card">
    <form method="get" action="{{ route('patients.index') }}" class="search-bar">
        <label class="sr-only" for="patient-search">جست‌وجوی بیمار</label>
        <input id="patient-search" name="q" value="{{ $search }}" placeholder="نام، نام خانوادگی، شماره پرونده یا موبایل" autocomplete="off">
        <button class="button button--primary" type="submit">جست‌وجو</button>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <caption class="sr-only">فهرست بیماران</caption>
            <thead><tr><th>شماره پرونده</th><th>نام</th><th>موبایل</th><th>کد ملی</th><th>وضعیت</th><th>اقدام</th></tr></thead>
            <tbody>
            @forelse ($patients as $patient)
                <tr>
                    <td dir="ltr"><bdi>{{ $patient->patient_no }}</bdi></td>
                    <td><strong>{{ $patient->fullName() }}</strong></td>
                    <td dir="ltr"><bdi>{{ $patient->mobile }}</bdi></td>
                    <td dir="ltr"><bdi>{{ $patient->national_id }}</bdi></td>
                    <td><span class="status-badge status-badge--success">{{ $patient->status }}</span></td>
                    <td><a class="button button--ghost button--small" href="{{ route('patients.show', ['patientId' => $patient->id]) }}">مشاهده پرونده</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">پرونده‌ای برای نمایش وجود ندارد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($patients->hasPages())<div class="pagination-wrap">{{ $patients->links() }}</div>@endif
</section>
@endsection
