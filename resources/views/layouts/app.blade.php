@php
    $platformName = app(\App\Support\PlatformSettings::class)->get('product_name', config('app.name', 'Disweb Dental SaaS'));
    $brandName = app(\App\Support\PlatformSettings::class)->get('brand_name', 'Disweb');
    $canViewPatients = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'patients.view');
    $canReviewQr = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'patients.create');
    $unreadNotifications = auth()->check() && session('active_tenant_id') ? auth()->user()->notifications()->where('tenant_id', session('active_tenant_id'))->where('status', 'unread')->count() : 0;
    $defaultFont = app(\App\Support\PlatformSettings::class)->get('default_font', 'Vazirmatn');
    $fontFamily = match ($defaultFont) {
        'Tahoma' => 'Tahoma, sans-serif',
        'Arial' => 'Arial, sans-serif',
        default => "'Vazirmatn', Tahoma, sans-serif",
    };
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $platformName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: {{ $fontFamily }}; }</style>
</head>
<body class="app-shell">
    <a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>

    <header class="topbar">
        <div class="topbar__brand">
            <span class="brand-mark" aria-hidden="true">D</span>
            <div>
                <span class="eyebrow">{{ $brandName }}</span>
                <strong>{{ $platformName }}</strong>
            </div>
        </div>
        @auth
            <div class="topbar__user">
                <span>{{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="button button--ghost" type="submit">خروج</button>
                </form>
            </div>
        @endauth
    </header>

    <div class="app-layout">
        @auth
            <aside class="sidebar" aria-label="ناوبری اصلی">
                <p class="sidebar__caption">مدیریت سامانه</p>
                <nav class="sidebar__nav">
                    <a class="nav-link nav-link--active" href="{{ route('dashboard') }}">داشبورد</a>
                    @if (auth()->user()->isSystemAdmin())
                        <a class="nav-link" href="{{ route('tenants.index') }}">کلینیک‌ها</a>
                        <a class="nav-link" href="{{ route('tenants.admin.settings.appearance') }}">تنظیمات ظاهر</a>
                    @endif
                    @if (session('active_tenant_id'))
                        <a class="nav-link" href="{{ route('branches.index') }}">شعبه‌ها</a>
                    @endif
                    @if (session('active_tenant_id') && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'users.view'))
                        <a class="nav-link" href="{{ route('clinic-users.index') }}">کاربران کلینیک</a>
                    @endif
                    @if ($canViewPatients && session('active_tenant_id'))
                        <a class="nav-link" href="{{ route('patients.index') }}">بیماران</a>
                    @else
                        <span class="nav-link nav-link--disabled">بیماران <small>محدود</small></span>
                    @endif
                    @if (session('active_tenant_id') && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'scheduling.view'))
                        <a class="nav-link" href="{{ route('calendar.index') }}">تقویم نوبت‌ها</a>
                    @endif
                    @if (session('active_tenant_id') && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'scheduling.create'))
                        <a class="nav-link" href="{{ route('appointments.create') }}">ثبت نوبت</a>
                    @endif
                    @if (session('active_tenant_id') && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'clinical.update'))
                        <a class="nav-link" href="{{ route('clinical-fields.index') }}">فیلدهای پرونده</a>
                    @endif
                    @if (session('active_tenant_id') && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'treatments.update'))
                        <a class="nav-link" href="{{ route('treatment-stages.index') }}">مراحل درمان</a>
                    @endif
                    @if (session('active_tenant_id') && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'finance.view'))
                        <a class="nav-link" href="{{ route('invoices.index') }}">فاکتورها</a>
                    @endif
                    @if ($canReviewQr && session('active_tenant_id'))
                        <a class="nav-link" href="{{ route('qr-requests.index') }}">درخواست‌های QR</a>
                    @endif
                    @if (session('active_tenant_id'))
                        <a class="nav-link" href="{{ route('notifications.index') }}">اعلان‌ها @if ($unreadNotifications > 0)<small>{{ $unreadNotifications }}</small>@endif</a>
                    @endif
                    <span class="nav-link nav-link--disabled">گزارش‌ها <small>به‌زودی</small></span>
                </nav>
            </aside>
        @endauth

        <main id="main-content" class="main-content">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert--danger" role="alert">
                    <strong>لطفاً موارد زیر را بررسی کنید:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</body>
</html>
