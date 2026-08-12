# Dent Dental SaaS

پلتفرم SaaS چندمستاجری مدیریت کلینیک‌های دندان‌پزشکی با **PHP/Laravel 12**، MySQL، رابط فارسی و راست‌چین، فونت آفلاین Vazirmatn و معماری مناسب استقرار روی cPanel.

> وضعیت فعلی: **MVP قابل انتشار برای staging و نصب آزمایشی**. قبل از استفاده با اطلاعات واقعی بیماران، اتصال MySQL، HTTPS، Backup، Cron و Smoke Test روی هاست واقعی باید تأیید شوند.

## قابلیت‌های نسخهٔ فعلی

این نسخه شامل ویزارد نصب، احراز هویت موبایل/نام کاربری، سوپرادمین، جداسازی Tenant، مدیریت کلینیک و شعبه، مدیریت پزشک و منشی، نقش و مجوز، ثبت بیمار با QR و صف تأیید، پروندهٔ پایه، حساسیت و دارو، اعلان داخلی، تقویم هفتگی شمسی، نوبت‌دهی و کنترل هم‌پوشانی، مراحل و طرح درمان، فاکتور و پرداخت، Audit و تنظیم فونت پنل است.

قابلیت‌های نمودار گرافیکی دندان، فایل پزشکی، نسخه‌نویسی، اتصال واقعی IPPanel/SMS، ایمیل، درگاه پرداخت، پورتال کامل بیمار، گزارش‌ساز پیشرفته، Queue تولیدی و Upgrade Center در Backlog نسخه‌های بعدی قرار دارند.

## فناوری و الزامات

| بخش | مقدار |
|---|---|
| Backend | PHP 8.2/8.3 و Laravel 12 |
| Database | MySQL در تولید؛ SQLite برای توسعه/تست |
| Frontend | Blade، Vite و CSS راست‌چین |
| Font | Vazirmatn محلی در `public/fonts` |
| Web root | حتماً پوشهٔ `public` |
| Production | HTTPS، `APP_DEBUG=false` و Backup الزامی |

## دانلود Release تمیز

هر Release رسمی با الگوی `dent-release-{version}-cpanel.zip` و فایل SHA256 منتشر می‌شود. این ZIP عمداً **بدون `vendor`، `node_modules`، `.env`، `.git`، Log، Cache، SQLite محلی، فایل Backup و تست‌های توسعه** ساخته می‌شود. این کار از انتقال اطلاعات محیط سازنده و وابستگی به فایل‌های اضافی جلوگیری می‌کند.

پس از دانلود، SHA256 را با فایل `.sha256` مقایسه کنید. فایل `composer.lock` داخل Release وجود دارد و نسخهٔ دقیق وابستگی‌ها را تثبیت می‌کند. پوشه‌های runtime لازم مانند `bootstrap/cache` و `storage` با ساختار خالی داخل بسته باقی می‌مانند، اما cacheهای PHP تولیدشده در محیط توسعه هرگز منتشر نمی‌شوند.

## نصب روی Windows/Laragon

پیش‌نیازها PHP 8.2 یا 8.3، Composer و برای دریافت مستقیم از Git، Node.js LTS با Corepack/pnpm هستند. ZIP را Extract کنید یا پروژه را Clone کنید، سپس از ریشهٔ پروژه، یعنی جایی که `composer.json` و `artisan` قرار دارند، فایل `install-laragon.bat` را اجرا کنید. این اسکریپت idempotent است: `.env`، APP_KEY، SQLite، مسیرهای runtime، Composer، Migration، cache و assetهای Vite را خودکار آماده می‌کند. در دریافت Git، `pnpm install --frozen-lockfile` و `pnpm run build` نیز اجرا می‌شوند؛ در Release تمیز، assetهای آمادهٔ `public/build` استفاده می‌شوند. در پایان، اگر نصب کامل نشده باشد `/install` و اگر نصب تکمیل شده باشد `/login` به‌صورت خودکار باز می‌شود. اجرای مجدد اسکریپت پس از Update نیز امن است.

اگر Composer قبلاً روی `vendor` ناقص اجرا شده است، در Git Bash این دستورات را اجرا کنید:

```bash
rm -rf vendor
composer install --no-interaction --prefer-dist --no-scripts
composer dump-autoload --optimize --no-scripts
cp .env.example .env
touch database/database.sqlite
php artisan key:generate --force
php artisan package:discover --ansi
php artisan migrate --force
php artisan optimize:clear
```

در Command Prompt ویندوز به‌جای `rm -rf vendor` از `rmdir /s /q vendor` استفاده کنید. فایل `composer.lock` را حذف نکنید.

## نصب روی cPanel با SSH یا Terminal

در cPanel دیتابیس و User بسازید، ZIP تمیز را خارج از `public_html` Extract کنید و Document Root را روی پوشهٔ `public` قرار دهید. از `.env.example` یک `.env` بسازید و `APP_ENV=production`، `APP_DEBUG=false`، `APP_URL` با HTTPS، `APP_TIMEZONE=Asia/Tehran`، `APP_LOCALE=fa` و تنظیمات MySQL را وارد کنید.

سپس در مسیر پروژه اجرا کنید:

```bash
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan optimize:clear
```

بعد آدرس `/install` را باز کنید و سوپرادمین اولیه را بسازید. مسیر نصب پس از تکمیل قفل می‌شود.

## نصب cPanel بدون SSH

Release تمیز بدون `vendor` است؛ بنابراین هاست باید یکی از این امکانات را داشته باشد: Composer Manager/Setup PHP App در cPanel، Terminal داخلی، یا اجرای Composer توسط پشتیبانی هاست. در Composer Manager مسیر پروژه را انتخاب کنید و دستور زیر را اجرا کنید:

```bash
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
```

اگر Composer Manager و Terminal وجود ندارد، از پشتیبانی بخواهید همین Composer command را در مسیر پروژه اجرا کند. **فایل PHP عمومی برای اجرای `shell_exec` یا Composer روی اینترنت نسازید.** اگر هاست نه Composer دارد، نه Terminal و نه همکاری پشتیبانی، Laravel روی آن هاست قابل نصب و نگهداری مطمئن نیست.

پس از ساخته‌شدن `vendor`، `.env` را در File Manager تنظیم کنید، Document Root را روی `public` بگذارید و `/install` را در مرورگر باز کنید. راهنمای کامل در [`docs/operations/cpanel-no-ssh-fa.md`](docs/operations/cpanel-no-ssh-fa.md) قرار دارد.

## Cron در cPanel بدون SSH

از بخش **Cron Jobs** یک Job هر دقیقه بسازید. مسیر PHP به شرکت هاست بستگی دارد؛ نمونه‌ها:

```text
/usr/local/bin/php /home/USERNAME/app/artisan schedule:run >> /dev/null 2>&1
```

یا:

```text
/opt/cpanel/ea-php83/root/usr/bin/php /home/USERNAME/app/artisan schedule:run >> /dev/null 2>&1
```

`USERNAME` و مسیر پروژه را تغییر دهید. یادآوری نوبت، Backup و Jobهای زمان‌بندی‌شده بدون Cron اجرا نمی‌شوند.

## ورود و خطاهای اعتبارسنجی

پس از تکمیل ویزارد، در صفحهٔ `/login` می‌توانید با نام کاربری یا شمارهٔ موبایلی که هنگام نصب ثبت کرده‌اید وارد شوید. پیام‌های الزامی فرم ورود فارسی هستند؛ اگر نسخهٔ قدیمی پیام خامی مانند `validation.required` نشان داد، Release جدید را دریافت کنید یا از شاخهٔ `main` به‌روزرسانی کرده و cache را پاک نمایید.

## ارتقای نسخه

ابتدا از دیتابیس، Storage و `.env` Backup بگیرید. Release جدید را در پوشهٔ موقت Extract کنید و `.env` و فایل‌های کاربر را از نسخهٔ قبلی حفظ کنید. فایل‌های برنامه را جایگزین کنید، سپس Composer را بر اساس `composer.lock` اجرا کنید و Migration را اعمال نمایید:

```bash
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
```

بدون SSH این فرمان‌ها باید از Composer Manager، Terminal داخلی یا توسط پشتیبانی هاست اجرا شوند. اجرای Migration با فایل PHP عمومی مجاز نیست. بعد از ارتقا Login، Dashboard، QR، بیماران، تقویم و فاکتور را Smoke Test کنید. جزئیات در [`docs/operations/cpanel-deployment-fa.md`](docs/operations/cpanel-deployment-fa.md) و [`docs/operations/cpanel-no-ssh-fa.md`](docs/operations/cpanel-no-ssh-fa.md) ثبت شده است.

## توسعه و تست

برای توسعه، پس از `composer install` از `.env.example` یک `.env` بسازید، SQLite را ایجاد کنید، سپس `pnpm install` و `pnpm run build` را اجرا کنید. کنترل‌های پذیرش عبارت‌اند از:

```bash
composer validate --strict
composer audit
vendor/bin/pint --test
php artisan view:cache
php artisan test
pnpm run build
git diff --check
```

هر فاز فقط پس از تست، رفع خطا، Regression و گزارش QA قابل قبول است.

## ساختار مستندات و سیاست انتشار

معماری، نیازمندی‌ها، امنیت، UX/RTL، استقرار cPanel، راهنمای بدون SSH و گزارش‌های QA در [`docs/`](docs/) قرار دارند. سیاست رسمی ساخت ZIP تمیز در [`docs/operations/release-policy-fa.md`](docs/operations/release-policy-fa.md) است.

هر آپدیت باید در همان Commit، README و CHANGELOG را به‌روز کند، از Commit مشخص ساخته شود، فایل SHA256 داشته باشد و در ZIP هیچ `.env`، `vendor`، `node_modules`، Log، Cache، دادهٔ محلی یا فایل توسعه‌ای باقی نگذارد.

## مشارکت و گزارش خطا

برای گزارش خطا، نسخهٔ Release، سیستم‌عامل، PHP، Composer، نوع هاست، آخرین دستور موفق و متن خطا را بدون ارسال رمز دیتابیس، APP_KEY، Session یا اطلاعات بیمار ثبت کنید. قبل از هر اصلاح، از دیتابیس و Storage Backup بگیرید.
