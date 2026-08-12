# تغییرات نسخهٔ آزمایشی

## نسخهٔ 0.1.2 — 2026-08-12

اسکریپت Release تمیز اصلاح شد تا فایل‌های cache PHP محیط توسعه، از جمله manifestهای وابسته به Laravel Pail، وارد ZIP عمومی نشوند؛ در عین حال `bootstrap/cache` و مسیرهای runtime موردنیاز Laravel به‌صورت ساختار خالی باقی می‌مانند. Smoke Test از ZIP استخراج‌شده با نصب `composer --no-dev`، تولید APP_KEY، package discovery، migration، پاک‌سازی cache و اجرای Laravel 12.66.0 با موفقیت کامل شد. ترتیب دستورات نصب Laragon و cPanel نیز برای اجرای Migration پیش از `optimize:clear` همگام شد.

## نسخهٔ 0.1.0 — 2026-08-12

این نسخه اسکلت Laravel 12، ویزارد نصب، احراز هویت مبتنی بر موبایل/نام کاربری، نقش‌ها و مجوزهای Tenant، مدیریت کلینیک و شعبه، Audit، QR بیمار، صف تأیید، پروندهٔ پایه، حساسیت و دارو، تقویم هفتگی شمسی، نوبت‌دهی با کنترل هم‌پوشانی، مراحل و طرح درمان، اعلان داخلی، مدیریت پزشک/منشی، فاکتور/پرداخت و تنظیم فونت آفلاین را اضافه می‌کند.

در این نسخه ۲۸ تست و ۹۵ assertion موفق، Laravel Pint روی ۱۱۱ فایل، Blade view cache، Composer validation/audit و Vite build اجرا شده است. نسخه برای staging و بررسی cPanel آماده است؛ استفادهٔ Production با دادهٔ واقعی منوط به اتصال MySQL، HTTPS، APP_DEBUG=false، بکاپ، Cron و Smoke Test هاست است.

### Backlog

نمودار گرافیکی دندان، Custom Field کامل، فایل پزشکی، نسخه‌نویسی، SMS/IPPanel، Email، درگاه پرداخت، پورتال بیمار، گزارش‌ساز، Queue/Worker تولیدی، backup/restore UI و تست بار کنترل‌شده در نسخه‌های بعدی تکمیل خواهند شد.


## اصلاحیهٔ 0.1.1 — 2026-08-12

خطای نصب در حالتی که `vendor` ناقص است مستندسازی و رفع شد. فایل `install-laragon.bat` اضافه شد تا ابتدا وابستگی‌های کامل Laravel با `composer install --no-scripts` نصب شوند و سپس autoload، package discovery، cache و migration اجرا شوند. راهنمای فارسی `docs/operations/laragon-install-fa.md` نیز اضافه شد. نصب تمیز بدون vendor در محیط ایزوله با موفقیت تأیید شد.


## نسخهٔ انتشار تمیز — 2026-08-12

سیاست رسمی Release اضافه شد: بستهٔ عمومی بدون `vendor`، `node_modules`، `.env`، Log، Cache، دادهٔ محلی و فایل‌های توسعه ساخته می‌شود. README گیت‌هاب، INSTALL و راهنماهای cPanel با SSH و بدون SSH بازنویسی شدند. اسکریپت `scripts/build-clean-release.sh` برای ساخت ZIP تکرارپذیر و کنترل SHA256 اضافه شد.
