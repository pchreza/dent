@php
    $platformName = app(\App\Support\PlatformSettings::class)->get('product_name', config('app.name', 'Disweb Dental SaaS'));
    $brandName = app(\App\Support\PlatformSettings::class)->get('brand_name', 'Disweb');
    $canViewPatients = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'patients.view');
    $canReviewQr = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'patients.create');
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $platformName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                    @endif
                    @if (session('active_tenant_id'))
                        <a class="nav-link" href="{{ route('branches.index') }}">شعبه‌ها</a>
                    @endif
                    @if ($canViewPatients && session('active_tenant_id'))
                        <a class="nav-link" href="{{ route('patients.index') }}">بیماران</a>
                    @else
                        <span class="nav-link nav-link--disabled">بیماران <small>محدود</small></span>
                    @endif
                    @if ($canReviewQr && session('active_tenant_id'))
                        <a class="nav-link" href="{{ route('qr-requests.index') }}">درخواست‌های QR</a>
                    @endif
                    <span class="nav-link nav-link--disabled">تقویم و نوبت <small>به‌زودی</small></span>
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
