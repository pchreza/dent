# راهنمای نصب نسخهٔ آزمایشی SaaS کلینیک دندان‌پزشکی

## پیش‌نیاز

هاست باید PHP 8.2 یا 8.3، MySQL سازگار، افزونه‌های PDO MySQL، Mbstring، OpenSSL، XML، Ctype، JSON، Fileinfo، Tokenizer و Curl، SSL فعال و امکان تنظیم Document Root و Cron داشته باشد.

## نصب روی cPanel

محتوای بسته را در مسیر امن خارج از `public_html` Extract کنید و Document Root دامنه را روی پوشهٔ `public` قرار دهید. اگر امکان تغییر Document Root ندارید، ساختار هاست را با راهنمای شرکت ارائه‌دهنده هماهنگ کنید و هرگز `app`، `storage`، `.env` و `vendor` را در معرض دانلود عمومی قرار ندهید.

از `.env.example` یک `.env` بسازید و این مقادیر را با اطلاعات واقعی تکمیل کنید: `APP_ENV=production`، `APP_DEBUG=false`، `APP_URL` با HTTPS، `APP_TIMEZONE=Asia/Tehran`، `APP_LOCALE=fa`، `DB_CONNECTION=mysql`، اطلاعات دیتابیس و `APP_KEY`. سپس Permission امن برای `storage` و `bootstrap/cache` تنظیم کنید.

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
