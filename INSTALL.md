# راهنمای نصب نسخهٔ آزمایشی SaaS کلینیک دندان‌پزشکی

> Release رسمی پروژه تمیز است و `vendor`، `node_modules`، `.env`، Log، Cache، دادهٔ محلی و فایل‌های توسعه را حمل نمی‌کند. وابستگی‌های PHP باید در مقصد با `composer.lock` نصب شوند. سیاست کامل در `docs/operations/release-policy-fa.md` قرار دارد.

## دریافت و کنترل Release

فایل `dent-release-{version}-cpanel.zip` را از Release رسمی دریافت کنید و SHA256 آن را با فایل `.sha256` مقایسه نمایید. ZIP را در مسیر امن Extract کنید و پیش از نصب مطمئن شوید `.env` واقعی و دادهٔ محلی در آن نیست.

## پیش‌نیاز

هاست باید PHP 8.2 یا 8.3، MySQL سازگار، افزونه‌های PDO MySQL، Mbstring، OpenSSL، XML، Ctype، JSON، Fileinfo، Tokenizer و Curl، SSL فعال و امکان تنظیم Document Root و Cron داشته باشد.

## نصب وابستگی‌های PHP در مقصد

در مقصد دارای SSH، Terminal داخلی cPanel یا Composer Manager، از ریشهٔ پروژه اجرا کنید:

```bash
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
```

بدون هیچ روش اجرای Composer، Laravel قابل نصب امن نیست؛ از پشتیبانی هاست بخواهید دستور بالا را در مسیر پروژه اجرا کند. ساخت فایل PHP عمومی برای اجرای Composer توصیه نمی‌شود.

## رفع خطای Autoload در Laragon/Windows

اگر با خطای `Class Illuminate\\Foundation\\Application not found` یا `Illuminate\\Foundation\\ComposerScripts is not autoloadable` روبه‌رو شدید، ابتدا `vendor` ناقص را حذف و وابستگی‌ها را نصب کنید؛ `composer dump-autoload` به‌تنهایی framework حذف‌شده را دانلود نمی‌کند:

```bash
rm -rf vendor
composer install --no-interaction --prefer-dist --no-scripts
composer dump-autoload --optimize --no-scripts
php artisan key:generate --force
php artisan package:discover --ansi
php artisan migrate --force
php artisan optimize:clear
```

در Command Prompt ویندوز، به‌جای `rm -rf vendor` از `rmdir /s /q vendor` استفاده کنید. همچنین می‌توانید فایل `install-laragon.bat` را از ریشهٔ پروژه اجرا کنید. جزئیات در `docs/operations/laragon-install-fa.md` آمده است.

## نصب روی cPanel

محتوای بسته را در مسیر امن خارج از `public_html` Extract کنید و Document Root دامنه را روی پوشهٔ `public` قرار دهید. اگر امکان تغییر Document Root ندارید، ساختار هاست را با راهنمای شرکت ارائه‌دهنده هماهنگ کنید و هرگز `app`، `storage`، `.env` و `vendor` را در معرض دانلود عمومی قرار ندهید.

از `.env.example` یک `.env` بسازید و این مقادیر را با اطلاعات واقعی تکمیل کنید: `APP_ENV=production`، `APP_DEBUG=false`، `APP_URL` با HTTPS، `APP_TIMEZONE=Asia/Tehran`، `APP_LOCALE=fa`، `DB_CONNECTION=mysql`، اطلاعات دیتابیس و `APP_KEY`. سپس Permission امن برای `storage` و `bootstrap/cache` تنظیم کنید. Release این مسیرها را به‌صورت ساختار خالی دارد و cacheهای PHP محیط توسعه را حمل نمی‌کند.

بعد از ساخت دیتابیس، در Terminal cPanel یا SSH این فرمان‌ها را اجرا کنید:

```bash
composer dump-autoload --optimize
php artisan migrate --force
php artisan optimize:clear
```

سپس آدرس `/install` را باز کنید، پیش‌نیازها را بررسی کنید و سوپرادمین اولیه را بسازید. پس از تکمیل نصب، ورود، ساخت Tenant، ساخت شعبه، فرم QR، تقویم، پرونده، فاکتور و تنظیمات ظاهر را Smoke Test کنید.

## Cron و پشتیبان‌گیری

برای این نسخه، Cron پایهٔ پیشنهادی زیر است و باید مسیر واقعی هاست جایگزین شود:

```cron
* * * * * cd /home/USER/app && php artisan schedule:run >> /dev/null 2>&1
```

قبل از هر Migration یا Upgrade از دیتابیس و Storage نسخهٔ پشتیبان بگیرید. فایل `.env`، Log، بکاپ و دادهٔ بیماران نباید در ZIP عمومی یا Git قرار گیرند.

## عیب‌یابی سریع

اگر صفحهٔ سفید یا خطای 500 دیدید، `APP_DEBUG=false` را روشن نکنید؛ Log کنترل‌شدهٔ `storage/logs` را بررسی کنید، Permission پوشه‌ها و اتصال MySQL را کنترل کنید، `php artisan optimize:clear` را اجرا کنید و در صورت شکست Migration از بکاپ و نسخهٔ قبلی بازگردید. در صورت خطای فونت، وجود فایل `public/fonts/vazirmatn-arabic-wght-normal.woff2` و دسترسی HTTPS آن را در مرورگر بررسی کنید.

## محدودهٔ نسخه

این بسته هستهٔ عملیاتی MVP را تحویل می‌دهد: نصب، احراز هویت، Tenant، نقش‌ها، کلینیک/شعبه، پزشک/منشی، QR بیمار، پروندهٔ پایه، تقویم شمسی، نوبت، مراحل/طرح درمان، اعلان داخلی، فاکتور/پرداخت و تنظیم فونت. موارد پیشرفته مانند نمودار دندان، فایل پزشکی، IPPanel، درگاه پرداخت، گزارش‌ساز و پورتال کامل بیمار در نسخه‌های بعدی قرار دارند.
