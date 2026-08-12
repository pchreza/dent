<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\InstallationState;
use App\Support\PlatformSettings;
use App\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InstallationState::class);
        $this->app->singleton(PlatformSettings::class);
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(10)->by(strtolower((string) $request->input('identifier')).'|'.$request->ip());
        });

        RateLimiter::for('install', function (Request $request): Limit {
            return Limit::perMinute(3)->by($request->ip());
        });
    }
}
