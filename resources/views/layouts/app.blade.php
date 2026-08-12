@php
    $platformName = app(\App\Support\PlatformSettings::class)->get('product_name', 'Disweb Dental SaaS');
    $platformName = $platformName === 'Laravel' ? 'Disweb Dental SaaS' : $platformName;
    $brandName = app(\App\Support\PlatformSettings::class)->get('brand_name', 'Disweb');
    $authorization = app(\App\Support\AuthorizationService::class);
    $canViewPatients = auth()->check() && $authorization->allows(auth()->user(), 'patients.view');
    $canReviewQr = auth()->check() && $authorization->allows(auth()->user(), 'patients.create');
    $canViewBranches = auth()->check() && $authorization->allows(auth()->user(), 'branches.view');
    $activeTenant = auth()->check() && session('active_tenant_id')
        ? \App\Models\Tenant::query()->find(session('active_tenant_id'))
        : null;
    $unreadNotifications = auth()->check() && $activeTenant
        ? auth()->user()->notifications()->where('tenant_id', $activeTenant->id)->where('status', 'unread')->count()
        : 0;
    $defaultFont = app(\App\Support\PlatformSettings::class)->get('default_font', 'Vazirmatn');
    $fontFamily = match ($defaultFont) {
        'Tahoma' => 'Tahoma, sans-serif',
        'Arial' => 'Arial, sans-serif',
        default => "'Vazirmatn', Tahoma, sans-serif",
    };
    $navClass = static fn (array $routes): string => 'nav-link'.(request()->routeIs(...$routes) ? ' nav-link--active' : '');
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
<body class="app-shell {{ auth()->check() ? 'app-shell--authenticated' : 'app-shell--public' }}">
    <a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>

    <header class="topbar">
        <div class="topbar__brand">
            @auth
                <button class="icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-label="باز کردن ناوبری اصلی" aria-controls="primary-navigation" aria-expanded="false">
                    <x-ui.icon name="menu" />
                </button>
            @endauth
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" aria-label="{{ $platformName }}">
                <span class="topbar__brand">
                    <span class="brand-mark" aria-hidden="true">D</span>
                    <span>
                        <span class="eyebrow">{{ $brandName }}</span>
                        <strong>{{ $platformName }}</strong>
                    </span>
                </span>
            </a>
        </div>

        @auth
            <div class="topbar__user">
                @if ($activeTenant)
                    <span class="topbar__context" title="کلینیک فعال">{{ $activeTenant->name }}</span>
                @elseif (auth()->user()->isSystemAdmin())
                    <span class="topbar__context">مدیریت سامانه</span>
                @endif

                @if (session('active_tenant_id'))
                    <a class="icon-button notification-button" href="{{ route('notifications.index') }}" aria-label="اعلان‌ها @if ($unreadNotifications > 0)؛ {{ $unreadNotifications }} اعلان خوانده‌نشده @endif">
                        <x-ui.icon name="bell" />
                        @if ($unreadNotifications > 0)<span aria-hidden="true">{{ $unreadNotifications }}</span>@endif
                    </a>
                @endif

                <details class="user-menu">
                    <summary aria-label="منوی کاربر">
                        <span class="user-menu__avatar" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                        <span class="user-menu__name">{{ auth()->user()->name }}</span>
                        <x-ui.icon name="chevron" size="16" />
                    </summary>
                    <div class="user-menu__panel">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ auth()->user()->isSystemAdmin() ? 'سوپرادمین' : 'کاربر کلینیک' }}</span>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button class="button button--ghost button--small" type="submit"><x-ui.icon name="logout" size="16" /> خروج امن</button>
                        </form>
                    </div>
                </details>
            </div>
        @endauth
    </header>

    <div class="app-layout">
        @auth
            <aside id="primary-navigation" class="sidebar" aria-label="ناوبری اصلی" tabindex="-1" data-sidebar>
                <div class="sidebar__mobile-head">
                    <strong>منوی سامانه</strong>
                    <button class="icon-button" type="button" data-sidebar-close aria-label="بستن ناوبری"><x-ui.icon name="close" /></button>
                </div>
                <p class="sidebar__caption">ناوبری سامانه</p>
                <nav class="sidebar__nav">
                    <div class="sidebar__group">
                        <span class="sidebar__group-title">نمای کلی</span>
                        <a class="{{ $navClass(['dashboard']) }}" href="{{ route('dashboard') }}"><span><x-ui.icon name="dashboard" size="18" /> داشبورد</span></a>
                    </div>

                    @if ($activeTenant)
                        <div class="sidebar__group">
                            <span class="sidebar__group-title">مراجعان و درمان</span>
                            @if ($canViewPatients)
                                <a class="{{ $navClass(['patients.*', 'dental-chart.*', 'treatment-plans.*']) }}" href="{{ route('patients.index') }}"><span><x-ui.icon name="patients" size="18" /> بیماران</span></a>
                            @else
                                <span class="nav-link nav-link--disabled"><span><x-ui.icon name="patients" size="18" /> بیماران</span><small>محدود</small></span>
                            @endif
                            @if ($authorization->allows(auth()->user(), 'clinical.update'))
                                <a class="{{ $navClass(['clinical-fields.*']) }}" href="{{ route('clinical-fields.index') }}"><span><x-ui.icon name="tooth" size="18" /> فیلدهای پرونده</span></a>
                            @endif
                            @if ($authorization->allows(auth()->user(), 'treatments.update'))
                                <a class="{{ $navClass(['treatment-stages.*']) }}" href="{{ route('treatment-stages.index') }}"><span><x-ui.icon name="treatment" size="18" /> مراحل درمان</span></a>
                            @endif
                        </div>

                        <div class="sidebar__group">
                            <span class="sidebar__group-title">برنامه‌ریزی</span>
                            @if ($authorization->allows(auth()->user(), 'scheduling.view'))
                                <a class="{{ $navClass(['calendar.*']) }}" href="{{ route('calendar.index') }}"><span><x-ui.icon name="calendar" size="18" /> تقویم نوبت‌ها</span></a>
                            @endif
                            @if ($authorization->allows(auth()->user(), 'scheduling.create'))
                                <a class="{{ $navClass(['appointments.*']) }}" href="{{ route('appointments.create') }}"><span><x-ui.icon name="plus" size="18" /> ثبت نوبت</span></a>
                            @endif
                        </div>

                        @if ($authorization->allows(auth()->user(), 'finance.view'))
                            <div class="sidebar__group">
                                <span class="sidebar__group-title">مالی</span>
                                <a class="{{ $navClass(['invoices.*']) }}" href="{{ route('invoices.index') }}"><span><x-ui.icon name="invoice" size="18" /> فاکتورها</span></a>
                            </div>
                        @endif

                        <div class="sidebar__group">
                            <span class="sidebar__group-title">مدیریت کلینیک</span>
                            @if ($canViewBranches)
                                <a class="{{ $navClass(['branches.*']) }}" href="{{ route('branches.index') }}"><span><x-ui.icon name="branch" size="18" /> شعبه‌ها</span></a>
                            @endif
                            @if ($authorization->allows(auth()->user(), 'users.view'))
                                <a class="{{ $navClass(['clinic-users.*']) }}" href="{{ route('clinic-users.index') }}"><span><x-ui.icon name="users" size="18" /> کاربران کلینیک</span></a>
                            @endif
                            @if ($canReviewQr)
                                <a class="{{ $navClass(['qr-requests.*']) }}" href="{{ route('qr-requests.index') }}"><span><x-ui.icon name="qr" size="18" /> درخواست‌های QR</span></a>
                            @endif
                            <a class="{{ $navClass(['notifications.*']) }}" href="{{ route('notifications.index') }}"><span><x-ui.icon name="bell" size="18" /> اعلان‌ها</span>@if ($unreadNotifications > 0)<small>{{ $unreadNotifications }}</small>@endif</a>
                        </div>
                    @endif

                    @if (auth()->user()->isSystemAdmin())
                        <div class="sidebar__group">
                            <span class="sidebar__group-title">سامانه</span>
                            <a class="{{ $navClass(['tenants.*']) }}" href="{{ route('tenants.index') }}"><span><x-ui.icon name="clinic" size="18" /> کلینیک‌ها</span></a>
                            <a class="{{ $navClass(['tenants.admin.settings.appearance']) }}" href="{{ route('tenants.admin.settings.appearance') }}"><span><x-ui.icon name="settings" size="18" /> تنظیمات ظاهر</span></a>
                        </div>
                    @endif
                </nav>
            </aside>
            <div class="sidebar-backdrop" data-sidebar-backdrop></div>
        @endauth

        <main id="main-content" class="main-content">
            @auth
                @if (! request()->routeIs('dashboard'))
                    <nav class="breadcrumb" aria-label="مسیر فعلی">
                        <a href="{{ route('dashboard') }}"><x-ui.icon name="dashboard" size="15" /> داشبورد</a>
                        <x-ui.icon name="chevron" size="14" aria-hidden="true" />
                        <span aria-current="page">{{ $title ?? $platformName }}</span>
                    </nav>
                @endif
            @endauth

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
