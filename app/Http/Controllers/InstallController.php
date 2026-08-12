<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\InstallationState;
use App\Support\NormalizeIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

final class InstallController extends Controller
{
    public function __construct(private readonly InstallationState $installationState) {}

    public function index(): View
    {
        return view('install.index', [
            'requirements' => [
                'PHP 8.2 یا 8.3' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'PDO MySQL یا SQLite' => extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite'),
                'Mbstring' => extension_loaded('mbstring'),
                'XML' => extension_loaded('xml'),
                'Fileinfo' => extension_loaded('fileinfo'),
                'پوشهٔ Storage قابل نوشتن' => is_writable(storage_path()),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:120'],
            'brand_name' => ['required', 'string', 'max:120'],
            'timezone' => ['required', 'timezone'],
            'admin_name' => ['required', 'string', 'max:160'],
            'mobile' => ['required', 'string', 'max:20'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:80'],
            'password' => ['required', 'string', 'min:10', 'max:200', 'confirmed'],
        ]);

        $mobile = NormalizeIdentifier::mobile($validated['mobile']);
        $username = NormalizeIdentifier::username($validated['username']);

        if ($mobile === '' || $username === '') {
            return back()->withInput()->withErrors(['mobile' => 'شماره موبایل یا نام کاربری معتبر نیست.']);
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            DB::transaction(function () use ($validated, $mobile, $username): void {
                $admin = User::query()->firstOrCreate(
                    ['username' => $username],
                    [
                        'name' => $validated['admin_name'],
                        'mobile' => $mobile,
                        'password' => $validated['password'],
                        'status' => 'active',
                        'is_system_admin' => true,
                        'must_change_password' => false,
                    ],
                );

                if (! $admin->is_system_admin) {
                    throw new \RuntimeException('حساب انتخاب‌شده نمی‌تواند سوپرادمین باشد.');
                }

                DB::table('platform_settings')->upsert([
                    [
                        'key' => 'product_name',
                        'value' => json_encode($validated['product_name'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'updated_by' => $admin->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'key' => 'brand_name',
                        'value' => json_encode($validated['brand_name'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'updated_by' => $admin->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'key' => 'timezone',
                        'value' => json_encode($validated['timezone'], JSON_THROW_ON_ERROR),
                        'updated_by' => $admin->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'key' => 'default_font',
                        'value' => json_encode('Vazirmatn', JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'updated_by' => $admin->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ], ['key'], ['value', 'updated_by', 'updated_at']);
            });

            $this->installationState->markInstalled();

            return redirect()->route('login')->with('status', 'نصب با موفقیت انجام شد. اکنون وارد سامانه شوید.');
        } catch (Throwable $exception) {
            $trackingCode = 'INS-'.Str::upper(Str::random(10));
            Log::error('Installation failed.', [
                'tracking_code' => $trackingCode,
                'exception' => $exception,
            ]);

            return back()->withInput()->withErrors([
                'installation' => "نصب انجام نشد. کد پیگیری: {$trackingCode}",
            ]);
        }
    }
}
