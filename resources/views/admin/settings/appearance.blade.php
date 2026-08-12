@extends('layouts.app', ['title' => 'تنظیمات ظاهر'])

@section('content')
<div class="page-header"><div><span class="eyebrow">پنل سوپرادمین</span><h1>تنظیمات ظاهر</h1><p class="muted">فونت پیش‌فرض تمام بخش‌های سامانه از اینجا کنترل می‌شود.</p></div></div>
<section class="card card--wide"><form method="post" action="{{ route('tenants.admin.settings.appearance.update') }}" class="stack-form">@csrf<div class="field"><label for="default_font">فونت پیش‌فرض</label><select id="default_font" name="default_font"><option value="Vazirmatn" @selected($defaultFont === 'Vazirmatn')>Vazirmatn — آفلاین و فارسی</option><option value="Tahoma" @selected($defaultFont === 'Tahoma')>Tahoma — فونت سیستم</option><option value="Arial" @selected($defaultFont === 'Arial')>Arial — فونت سیستم</option></select><small>Vazirmatn به‌صورت محلی در پروژه نگهداری شده و به CDN وابسته نیست.</small></div><div class="form-actions"><button class="button button--primary" type="submit">ذخیره تنظیمات</button></div></form></section>
@endsection
