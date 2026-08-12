# نصب و رفع خطای Autoload در Laragon/Windows

## علت خطای گزارش‌شده

خطای `Class Illuminate\Foundation\Application not found` یعنی Composer فایل `vendor/autoload.php` را دارد، اما وابستگی اصلی `laravel/framework` در `vendor` کامل نیست یا autoload قبل از نصب framework اجرا شده است. خطای `Illuminate\Foundation\ComposerScripts is not autoloadable` پیام ثانویهٔ همین وضعیت است؛ `composer dump-autoload` به‌تنهایی وابستگی‌های حذف‌شده را نصب نمی‌کند.

## راه سریع برای پروژهٔ فعلی

در PowerShell یا Git Bash از ریشهٔ پروژه اجرا کنید؛ مسیر باید همان جایی باشد که `composer.json` و `artisan` قرار دارند:

```bash
rm -rf vendor
composer install --no-interaction --prefer-dist --no-scripts
composer dump-autoload --optimize --no-scripts
php artisan key:generate --force
php artisan package:discover --ansi
php artisan optimize:clear
php artisan migrate --force
```

در **Command Prompt ویندوز** به‌جای `rm -rf vendor` از این دستور استفاده کنید:

```bat
rmdir /s /q vendor
```

فایل `composer.lock` را حذف نکنید؛ این فایل نسخه‌های تأییدشدهٔ پروژه را تثبیت می‌کند. پس از اجرای دستورها، `/install` را باز کنید و نصب سوپرادمین را از طریق ویزارد ادامه دهید.

## روش خودکار پیشنهادی

فایل `install-laragon.bat` در ریشهٔ پروژه قرار دارد. Laragon و Composer را باز کنید، مطمئن شوید Composer در PATH است و فایل را از کنار `composer.json` اجرا کنید. این اسکریپت وجود framework را بررسی می‌کند، در صورت ناقص‌بودن vendor آن را از روی lock دوباره نصب می‌کند، `.env` و SQLite محلی را می‌سازد، سپس autoload، key، package discovery، cache و migration را اجرا می‌کند.

## کنترل‌های لازم در Laragon

| کنترل | مقدار مورد انتظار |
|---|---|
| PHP | 8.2 یا 8.3 |
| Composer | در PATH سیستم و قابل اجرای `composer --version` |
| پوشهٔ پروژه | دارای `artisan` و `composer.json` |
| vendor | دارای `vendor/laravel/framework/src/Illuminate/Foundation/Application.php` |
| محیط محلی | `DB_CONNECTION=sqlite` و وجود `database/database.sqlite` |
| محیط واقعی | `APP_DEBUG=false`، HTTPS و MySQL تنظیم‌شده |
| Document Root | برای Apache/Nginx روی پوشهٔ `public` |

اگر بعد از `composer install --no-scripts` هنوز framework نصب نشد، خروجی `composer install -vvv --no-scripts` و نتیجهٔ `composer diagnose` را بررسی کنید؛ احتمالاً نسخهٔ PHP، افزونهٔ موردنیاز یا اتصال Composer به Packagist مشکل دارد. در این حالت `vendor` ناقص را نگه ندارید و نصب را از روی `composer.lock` دوباره انجام دهید.
