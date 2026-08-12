# نصب و ارتقا روی cPanel بدون SSH

## نکتهٔ مهم دربارهٔ وابستگی‌ها

Laravel برای اجرا به `vendor/` نیاز دارد. Release ZIP تمیز پروژه عمداً `vendor/` ندارد تا بستهٔ انتشار سبک، قابل بازتولید و بدون فایل‌های اضافی باشد. بنابراین در هاستی که SSH ندارد باید یکی از مسیرهای امن زیر برای اجرای Composer وجود داشته باشد:

1. **Composer Manager یا Setup PHP App در خود cPanel.** بعضی شرکت‌های هاست ابزار نصب وابستگی Composer را در بخش Application Manager ارائه می‌کنند.
2. **درخواست از پشتیبانی هاست.** پشتیبانی می‌تواند در مسیر پروژه `composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader` را اجرا کند.
3. **آپلود جداگانهٔ artifact وابستگی از یک محیط قابل اعتماد.** این artifact بخشی از Release ZIP تمیز نیست؛ باید دقیقاً از همان `composer.lock` ساخته شود، با SHA256 بررسی گردد و فقط در مسیر خارج از `public_html` Extract شود.

اگر هاست هیچ Composer Manager، Terminal، Cron قابل اجرا یا همکاری پشتیبانی ندارد، Laravel روی آن هاست قابل نصب و نگهداری مطمئن نیست. اجرای Composer از طریق یک فایل PHP عمومی یا `shell_exec` در وب توصیه نمی‌شود، چون ریسک اجرای ناخواسته و افشای اطلاعات دارد.

## نصب اولیه فقط با File Manager و Composer UI

ابتدا در cPanel از بخش **MySQL Databases** دیتابیس و User بسازید. سپس Release ZIP تمیز را از File Manager در مسیر امن، ترجیحاً خارج از `public_html`، Upload و Extract کنید. Document Root دامنه یا زیردامنه باید روی پوشهٔ `public` پروژه قرار گیرد؛ قرار دادن ریشهٔ Laravel در public باعث افشای فایل‌های حساس می‌شود.

در File Manager، از `.env.example` یک فایل `.env` کپی کنید و مقادیر Production را وارد کنید: `APP_ENV=production`، `APP_DEBUG=false`، `APP_URL` با HTTPS، `APP_TIMEZONE=Asia/Tehran`، `APP_LOCALE=fa`، `DB_CONNECTION=mysql`، Host، Port، نام دیتابیس، User و Password. فایل `.env` نباید در `public_html` قابل دانلود باشد.

در Composer Manager cPanel، مسیر پروژه را انتخاب و نصب Production را اجرا کنید. اگر گزینهٔ دستور وجود دارد، از این فرمان استفاده کنید:

```bash
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
```

پس از ایجاد `vendor/`، از File Manager Permission مناسب `storage` و `bootstrap/cache` را تنظیم کنید و آدرس `/install` را در مرورگر باز کنید. پیش‌نیازها، اتصال دیتابیس و سوپرادمین را از طریق ویزارد تکمیل کنید. در پایان، مسیر `/install` قفل می‌شود.

## اگر Composer UI هم وجود نداشت

از پشتیبانی هاست بخواهید فقط این کارها را در مسیر پروژه انجام دهد: اجرای Composer بر اساس `composer.lock`، تنظیم Permission پوشه‌ها، اجرای `php artisan migrate --force` در نصب اولیه و بررسی PHP extensions. متن درخواست باید شامل نسخهٔ PHP، مسیر پروژه و این دستور باشد:

```bash
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
```

رمز دیتابیس و محتوای `.env` را در تیکت عمومی ارسال نکنید؛ پشتیبانی باید از دسترسی داخلی cPanel استفاده کند یا شما مقدارها را در File Manager وارد کنید.

## Cron بدون SSH

از cPanel به **Cron Jobs** بروید و یک Job با تناوب هر دقیقه بسازید. مسیر PHP در هاست‌ها متفاوت است؛ نمونه‌ها:

```text
/usr/local/bin/php /home/USERNAME/app/artisan schedule:run >> /dev/null 2>&1
```

یا در سرورهای EasyApache:

```text
/opt/cpanel/ea-php83/root/usr/bin/php /home/USERNAME/app/artisan schedule:run >> /dev/null 2>&1
```

`USERNAME` و مسیر `app` را با مسیر واقعی جایگزین کنید. اگر Cron نیز در دسترس نیست، یادآوری نوبت، بکاپ خودکار و Jobهای زمان‌بندی‌شده قابل اتکا نخواهند بود و باید از سرویس خارجی یا هاست دیگری استفاده شود.

## ارتقای نسخه بدون SSH

قبل از Upgrade از cPanel Backup یا ابزار Backup Wizard از دیتابیس و Storage نسخهٔ پشتیبان بگیرید. نسخهٔ جدید را در پوشهٔ موقت Extract کنید، `.env`، Storage و فایل‌های کاربر را از نسخهٔ فعلی حفظ کنید و فقط فایل‌های برنامه را جایگزین کنید. سپس Composer Manager یا پشتیبانی هاست باید `composer install` را بر اساس `composer.lock` اجرا کند و Migration نسخه را با `php artisan migrate --force` اعمال نماید. بعد از آن `php artisan optimize:clear` اجرا شود و مسیرهای Login، Dashboard، QR، بیماران و تقویم Smoke Test شوند.

در حال حاضر مسیر ارتقای امن از طریق CLI/Composer Manager انجام می‌شود و نباید فایل PHP عمومی برای اجرای فرمان‌های Artisan روی اینترنت قرار گیرد. در نسخهٔ بعدی می‌توان Upgrade Center محدود، احراز هویت‌شده، one-time و Audit‌شده طراحی کرد.

## چک‌لیست نهایی بدون SSH

| مورد | نتیجهٔ لازم |
|---|---|
| Release ZIP | از نسخهٔ رسمی و دارای SHA256 باشد |
| `.env` | ساخته و خارج از public قابل محافظت باشد |
| `vendor/` | با Composer همان نسخه ساخته شده باشد |
| Document Root | روی `public` باشد |
| APP_DEBUG | در Production برابر `false` باشد |
| HTTPS | فعال و اجباری باشد |
| `/install` | بعد از نصب قفل شده باشد |
| Cron | در cPanel تنظیم و آخرین اجرا کنترل شود |
| Backup | پیش از نصب/ارتقا گرفته و بازیابی آن آزمایش شود |
