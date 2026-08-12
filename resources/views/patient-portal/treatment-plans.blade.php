@extends('layouts.patient')

@section('content')
    <header class="page-header">
        <div>
            <span class="eyebrow">درمان</span>
            <h1>طرح‌های درمان من</h1>
            <p>طرح‌ها و برآوردهای ثبت‌شده برای پروندهٔ شما در کلینیک فعال.</p>
        </div>
    </header>

    <section class="card table-card">
        @if ($treatmentPlans->isEmpty())
            <div class="empty-state">
                <strong>طرح درمانی برای نمایش وجود ندارد.</strong>
                <p>پس از ثبت طرح درمان توسط پزشک، خلاصهٔ آن در این بخش نمایش داده می‌شود.</p>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>عنوان طرح</th>
                            <th>تعداد آیتم</th>
                            <th>برآورد هزینه</th>
                            <th>وضعیت</th>
                            <th>شروع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($treatmentPlans as $plan)
                            <tr>
                                <td><strong>{{ $plan->title }}</strong></td>
                                <td><bdi dir="ltr">{{ $plan->items_count }}</bdi></td>
                                <td><bdi dir="ltr">{{ number_format((float) $plan->estimated_total) }}</bdi> ریال</td>
                                <td><span class="badge badge--info">{{ match($plan->status) {
                                    'proposed' => 'پیشنهادی',
                                    'approved' => 'تأییدشده',
                                    'in_progress' => 'در حال انجام',
                                    'completed' => 'تکمیل‌شده',
                                    'cancelled' => 'لغوشده',
                                    'rejected' => 'ردشده',
                                    default => $plan->status,
                                } }}</span></td>
                                <td>@if ($plan->started_on)<bdi dir="ltr">{{ \App\Support\JalaliDate::format($plan->started_on) }}</bdi>@else ثبت نشده @endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $treatmentPlans->links() }}</div>
        @endif
    </section>
@endsection
