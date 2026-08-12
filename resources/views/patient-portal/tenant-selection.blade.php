@extends('layouts.patient')

@section('content')
    <section class="auth-card patient-tenant-selection">
        <span class="brand-mark brand-mark--large" aria-hidden="true">D</span>
        <span class="eyebrow">پرتال بیمار</span>
        <h1>انتخاب کلینیک</h1>
        <p>برای مشاهدهٔ نوبت‌ها، طرح درمان و اطلاعات مالی، ابتدا کلینیک مورد نظر خود را انتخاب کنید.</p>

        <div class="stack-list" aria-label="کلینیک‌های در دسترس">
            @foreach ($accounts as $account)
                <form method="post" action="{{ route('patient.tenants.store', $account->tenant_id) }}">
                    @csrf
                    <button class="selection-card" type="submit">
                        <span>
                            <strong>{{ $account->tenant->name }}</strong>
                            <small><bdi dir="ltr">{{ $account->patient->patient_no }}</bdi> · پروندهٔ شما</small>
                        </span>
                        <x-ui.icon name="chevron" size="18" aria-hidden="true" />
                    </button>
                </form>
            @endforeach
        </div>
    </section>
@endsection
