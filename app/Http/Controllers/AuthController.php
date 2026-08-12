<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Support\NormalizeIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

final class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $identifier = (string) $request->validated('identifier');
        $key = 'login:'.strtolower($identifier).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withInput()->withErrors([
                'identifier' => "تلاش‌های ورود بیش از حد مجاز است. {$seconds} ثانیه دیگر دوباره تلاش کنید.",
            ]);
        }

        $credentials = [
            'password' => (string) $request->validated('password'),
            'status' => 'active',
        ];

        $mobile = NormalizeIdentifier::mobile($identifier);
        $credentials['mobile'] = $mobile;

        if (! Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            $credentials['username'] = NormalizeIdentifier::username($identifier);
            unset($credentials['mobile']);

            if (! Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
                RateLimiter::hit($key, 60);

                return back()->withInput()->withErrors([
                    'identifier' => 'اطلاعات ورود صحیح نیست یا حساب فعال نیست.',
                ]);
            }
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->patientAccounts()->exists()) {
            if ($user->patientAccounts()->count() > 1) {
                return redirect()->route('patient.tenants.index');
            }

            return redirect()->route($user->must_change_password ? 'patient.password.edit' : 'patient.dashboard');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'با موفقیت خارج شدید.');
    }
}
