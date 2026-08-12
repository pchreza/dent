@extends('layouts.app', ['title' => 'فاکتورها'])

@section('content')
<div class="page-header">
    <div><span class="eyebrow">{{ $tenant->name }}</span><h1>فاکتورها</h1><p class="muted">فاکتورها و پرداخت‌ها فقط در محدودهٔ کلینیک فعال نمایش داده می‌شوند.</p></div>
    <a class="button button--primary" href="{{ route('invoices.create') }}">صدور فاکتور</a>
</div>
<section class="card">
    <div class="table-wrap"><table class="data-table"><caption class="sr-only">فهرست فاکتورها</caption><thead><tr><th>شماره</th><th>بیمار</th><th>تاریخ</th><th>وضعیت</th><th>کل</th><th>پرداخت‌شده</th><th>مانده</th><th>اقدام</th></tr></thead><tbody>
    @forelse ($invoices as $invoice)
        <tr><td dir="ltr"><bdi>{{ $invoice->invoice_no }}</bdi></td><td>{{ $invoice->patient->fullName() }}</td><td dir="ltr"><bdi>{{ $invoice->issue_date?->format('Y-m-d') }}</bdi></td><td><span class="status-badge status-badge--info">{{ $invoice->status }}</span></td><td dir="ltr"><bdi>{{ number_format((float) $invoice->total, 2) }}</bdi></td><td dir="ltr"><bdi>{{ number_format((float) $invoice->paid_total, 2) }}</bdi></td><td dir="ltr"><bdi>{{ $invoice->balance() }}</bdi></td><td><a class="button button--ghost button--small" href="{{ route('invoices.show', ['invoiceId' => $invoice->id]) }}">مشاهده</a></td></tr>
    @empty
        <tr><td colspan="8" class="empty-state">فاکتوری وجود ندارد.</td></tr>
    @endforelse
    </tbody></table></div>
    @if ($invoices->hasPages())<div class="pagination-wrap">{{ $invoices->links() }}</div>@endif
</section>
@endsection
