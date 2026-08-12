@php
    $platformName = app(\App\Support\PlatformSettings::class)->get('product_name', 'Disweb Dental SaaS');
    $platformName = $platformName === 'Laravel' ? 'Disweb Dental SaaS' : $platformName;
    $brandName = app(\App\Support\PlatformSettings::class)->get('brand_name', 'Disweb');
    $activeTenant = app(\App\Support\TenantContext::class)->get();
    $unreadNotifications = auth()->user()?->notifications()
        ->when($activeTenant, static fn ($query) => $query->where('tenant_id', $activeTenant->id))
        ->where('status', 'unread')
        ->count() ?? 0;
    $navClass = static fn (array $routes): string => 'nav-link'.(request()->routeIs(...$routes) ? ' nav-link--active' : '');
    $portalTitles = [
        'patient.dashboard' => 'داشبورد من',
        'patient.appointments' => 'نوبت‌های من',
        'patient.treatment-plans' => 'طرح‌های درمان',
        'patient.invoices' => 'فاکتورهای من',
        'patient.notifications' => 'اعلان‌های من',
        'patient.password.edit' => 'تغییر رمز عبور',
        'patient.tenants.index' => 'انتخاب کلینیک',
    ];
    $pageTitle = $portalTitles[request()->route()?->getName()] ?? $platformName;
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Vazirmatn', Tahoma, sans-serif; }</style>
</head>
<body class="app-shell {{ $activeTenant ? 'app-shell--authenticated' : 'app-shell--public' }} patient-portal-shell">
    <a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>

    <header class="topbar">
        <div class="topbar__brand">
            @if ($activeTenant)
                <button class="icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-label="باز کردن ناوبری اصلی" aria-controls="patient-navigation" aria-expanded="false">
                    <x-ui.icon name="menu" />
                </button>
            @endif
            <a href="{{ $activeTenant ? route('patient.dashboard') : route('patient.tenants.index') }}" aria-label="{{ $platformName }}">
                <span class="topbar__brand">
                    <span class="brand-mark" aria-hidden="true">D</span>
                    <span>
                        <span class="eyebrow">{{ $brandName }}</span>
                        <strong>{{ $platformName }}</strong>
                    </span>
                </span>
            </a>
        </div>

        <div class="topbar__user">
            @if ($activeTenant)
                <a class="topbar__context" href="{{ route('patient.tenants.index') }}" title="تغییر کلینیک فعال">{{ $activeTenant->name }}</a>
                <a class="icon-button notification-button" href="{{ route('patient.notifications') }}" aria-label="اعلان‌ها @if ($unreadNotifications > 0)؛ {{ $unreadNotifications }} اعلان خوانده‌نشده @endif">
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
                    <span>پرتال بیمار</span>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="button button--ghost button--small" type="submit"><x-ui.icon name="logout" size="16" /> خروج امن</button>
                    </form>
                </div>
            </details>
        </div>
    </header>

    <div class="app-layout">
        @if ($activeTenant)
            <aside id="patient-navigation" class="sidebar" aria-label="ناوبری پورتال بیمار" tabindex="-1" data-sidebar>
                <div class="sidebar__mobile-head">
                    <strong>منوی بیمار</strong>
                    <button class="icon-button" type="button" data-sidebar-close aria-label="بستن ناوبری"><x-ui.icon name="close" /></button>
                </div>
                <p class="sidebar__caption">پرتال بیمار</p>
                <nav class="sidebar__nav">
                    <div class="sidebar__group">
                        <span class="sidebar__group-title">نمای کلی</span>
                        <a class="{{ $navClass(['patient.dashboard']) }}" href="{{ route('patient.dashboard') }}"><span><x-ui.icon name="dashboard" size="18" /> داشبورد من</span></a>
                    </div>
                    <div class="sidebar__group">
                        <span class="sidebar__group-title">خدمات من</span>
                        <a class="{{ $navClass(['patient.appointments']) }}" href="{{ route('patient.appointments') }}"><span><x-ui.icon name="calendar" size="18" /> نوبت‌های من</span></a>
                        <a class="{{ $navClass(['patient.treatment-plans']) }}" href="{{ route('patient.treatment-plans') }}"><span><x-ui.icon name="treatment" size="18" /> طرح‌های درمان</span></a>
                        <a class="{{ $navClass(['patient.invoices']) }}" href="{{ route('patient.invoices') }}"><span><x-ui.icon name="invoice" size="18" /> فاکتورها</span></a>
                    </div>
                    <div class="sidebar__group">
                        <span class="sidebar__group-title">حساب کاربری</span>
                        <a class="{{ $navClass(['patient.notifications']) }}" href="{{ route('patient.notifications') }}"><span><x-ui.icon name="bell" size="18" /> اعلان‌ها</span>@if ($unreadNotifications > 0)<small>{{ $unreadNotifications }}</small>@endif</a>
                        <a class="{{ $navClass(['patient.password.*']) }}" href="{{ route('patient.password.edit') }}"><span><x-ui.icon name="settings" size="18" /> تغییر رمز عبور</span></a>
                    </div>
                </nav>
            </aside>
            <div class="sidebar-backdrop" data-sidebar-backdrop></div>
        @endif

        <main id="main-content" class="main-content">
            @if ($activeTenant && ! request()->routeIs('patient.dashboard'))
                <nav class="breadcrumb" aria-label="مسیر فعلی">
                    <a href="{{ route('patient.dashboard') }}"><x-ui.icon name="dashboard" size="15" /> داشبورد من</a>
                    <x-ui.icon name="chevron" size="14" aria-hidden="true" />
                    <span aria-current="page">{{ $pageTitle }}</span>
                </nav>
            @endif

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
