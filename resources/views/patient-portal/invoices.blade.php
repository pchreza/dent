@extends('layouts.patient')

@section('content')
    <header class="page-header">
        <div>
            <span class="eyebrow">مالی</span>
            <h1>فاکتورهای من</h1>
            <p>فاکتورها و ماندهٔ قابل مشاهده برای پروندهٔ شما در کلینیک فعال.</p>
        </div>
    </header>

    <section class="card table-card">
        @if ($invoices->isEmpty())
            <div class="empty-state">
                <strong>فاکتوری برای نمایش وجود ندارد.</strong>
                <p>پس از ثبت فاکتور توسط کلینیک، خلاصهٔ آن در این بخش نمایش داده می‌شود.</p>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>شماره</th>
                            <th>تاریخ</th>
                            <th>کل</th>
                            <th>پرداخت‌شده</th>
                            <th>مانده</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td><bdi dir="ltr">{{ $invoice->invoice_no }}</bdi></td>
                                <td><bdi dir="ltr">{{ \App\Support\JalaliDate::format($invoice->issue_date) }}</bdi></td>
                                <td><bdi dir="ltr">{{ number_format((float) $invoice->total) }}</bdi> ریال</td>
                                <td><bdi dir="ltr">{{ number_format((float) $invoice->paid_total) }}</bdi> ریال</td>
                                <td><bdi dir="ltr">{{ number_format((float) $invoice->balance()) }}</bdi> ریال</td>
                                <td><span class="badge badge--info">{{ match($invoice->status) {
                                    'draft' => 'پیش‌نویس',
                                    'open' => 'باز',
                                    'partially_paid' => 'پرداخت بخشی',
                                    'paid' => 'پرداخت‌شده',
                                    'overdue' => 'سررسید گذشته',
                                    'cancelled' => 'لغوشده',
                                    'waived' => 'بخشوده',
                                    'refunded' => 'بازپرداخت‌شده',
                                    default => $invoice->status,
                                } }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $invoices->links() }}</div>
        @endif
    </section>
@endsection
