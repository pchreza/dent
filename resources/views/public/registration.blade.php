@extends('layouts.app', ['title' => 'ثبت‌نام بیمار'])

@section('content')
<div class="centered-page">
    <section class="card card--wide public-registration-card">
        <div class="section-heading">
            <div>
                <span class="eyebrow">{{ $tenant->name }}</span>
                <h1>ثبت‌نام بیمار</h1>
                <p class="muted">اطلاعات پایه را وارد کنید. پس از بررسی منشی یا پزشک، پروندهٔ شما فعال می‌شود.</p>
            </div>
            <span class="status-badge status-badge--info">فرم امن QR</span>
        </div>

        <div class="alert alert--info" role="note">
            اطلاعات این فرم فقط برای بررسی داخلی همین کلینیک استفاده می‌شود. از ارسال اطلاعات حساس غیرضروری خودداری کنید.
        </div>

        <form method="post" action="{{ route('public.registration.store', ['tenantCode' => $tenant->code]) }}" class="form-grid" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-section">
                <h2>مشخصات فردی</h2>
                <div class="field-grid">
                    <div class="field"><label for="first_name">نام <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="first_name" name="first_name" value="{{ old('first_name') }}" required></div>
                    <div class="field"><label for="last_name">نام خانوادگی <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="last_name" name="last_name" value="{{ old('last_name') }}" required></div>
                    <div class="field"><label for="national_id">کد ملی <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="national_id" name="national_id" value="{{ old('national_id') }}" required inputmode="numeric" dir="ltr"></div>
                    <div class="field"><label for="birth_date">تاریخ تولد</label><input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}" dir="ltr"></div>
                    <div class="field"><label for="gender">جنسیت</label><select id="gender" name="gender"><option value="">انتخاب کنید</option><option value="male" @selected(old('gender') === 'male')>مرد</option><option value="female" @selected(old('gender') === 'female')>زن</option><option value="other" @selected(old('gender') === 'other')>سایر</option><option value="unknown" @selected(old('gender') === 'unknown')>ترجیح می‌دهم نگویم</option></select></div>
                    <div class="field"><label for="mobile">موبایل <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label><input id="mobile" name="mobile" value="{{ old('mobile') }}" required inputmode="tel" dir="ltr"></div>
                    <div class="field"><label for="phone">تلفن ثابت</label><input id="phone" name="phone" value="{{ old('phone') }}" inputmode="tel" dir="ltr"></div>
                    <div class="field field--full"><label for="address">نشانی</label><textarea id="address" name="address" rows="3">{{ old('address') }}</textarea></div>
                    <div class="field"><label for="insurance_name">بیمه</label><input id="insurance_name" name="insurance_name" value="{{ old('insurance_name') }}"></div>
                </div>
            </div>

            <div class="form-section">
                <h2>تماس اضطراری</h2>
                <div class="field-grid">
                    <div class="field"><label for="emergency_name">نام شخص تماس</label><input id="emergency_name" name="emergency_name" value="{{ old('emergency_name') }}"></div>
                    <div class="field"><label for="emergency_mobile">موبایل شخص تماس</label><input id="emergency_mobile" name="emergency_mobile" value="{{ old('emergency_mobile') }}" inputmode="tel" dir="ltr"></div>
                </div>
            </div>

            <label class="checkbox-field consent-field">
                <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
                <span>با ثبت این فرم، با بررسی اطلاعات توسط کارکنان همین کلینیک موافقم. *</span>
            </label>
            <div class="form-actions"><button class="button button--primary" type="submit">ارسال درخواست ثبت‌نام</button></div>
        </form>
    </section>
</div>
@endsection
