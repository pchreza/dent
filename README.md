# Disweb Dental SaaS

پلتفرم SaaS چندمستاجری مدیریت کلینیک‌های دندان‌پزشکی، با PHP/Laravel، MySQL، رابط فارسی و راست‌چین، فونت آفلاین Vazirmatn و هدف استقرار روی cPanel بدون SSH.

## وضعیت پروژه

فاز صفر تکمیل شده است و فاز یک در حال توسعه است. خروجی فاز صفر شامل معماری، مدل داده، نیازمندی MVP، مدل تهدید، استراتژی تست، قرارداد RTL و راهنمای cPanel در پوشهٔ `docs/` قرار دارد.

> هیچ فازی بدون تست، گزارش خطا، رفع Regression و گیت پذیرش تکمیل‌شده تلقی نمی‌شود.

## فناوری

| بخش | فناوری |
|---|---|
| Backend | PHP 8.2/8.3، Laravel 12، معماری مونولیت ماژولار |
| Database | MySQL 8 در تولید؛ SQLite برای تست‌های محلی |
| UI | Blade، Vite، CSS منطقی RTL؛ Livewire/Alpine در فازهای تعاملی |
| Font | Vazirmatn به‌صورت آفلاین در `public/fonts` |
| Testing | PHPUnit/Laravel Test، Laravel Pint |
| Deployment | cPanel، ویزارد نصب، Cron Job، بستهٔ self-contained با Vendor |

## نصب توسعه

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
pnpm install
pnpm run build
php artisan serve
```

در اولین اجرای واقعی، مسیر `/install` ویزارد نصب را نمایش می‌دهد. در محیط توسعهٔ تستی، SQLite در `phpunit.xml` استفاده می‌شود. برای تولید، `DB_CONNECTION=mysql` و اطلاعات MySQL cPanel تنظیم می‌شوند.

## تست و کیفیت

```bash
php artisan test
vendor/bin/pint --test
pnpm run build
```

تست‌ها باید شامل Unit، Feature، امنیت، جداسازی Tenant، RTL و در فازهای عملیاتی شامل نصب/ارتقا/بکاپ باشند. کد با `declare(strict_types=1)` و نام‌گذاری روشن نوشته می‌شود.

## ساختار مستندات

- `docs/phase-0-report-fa.md`: گزارش فاز صفر و Backlog فاز یک.
- `docs/architecture/phase-0-architecture-fa.md`: معماری و لایه‌ها.
- `docs/architecture/domain-model-fa.md`: مدل دامنه و طرح جداول.
- `docs/product/mvp-requirements-fa.md`: نیازمندی‌های MVP و معیار پذیرش.
- `docs/security/threat-model-fa.md`: مدل تهدید و کنترل امنیتی.
- `docs/qa/phase-0-test-strategy-fa.md`: راهبرد تست و تعریف Done.
- `docs/ux/rtl-design-spec-fa.md`: قرارداد RTL، دسترس‌پذیری و Design System.
- `docs/operations/cpanel-deployment-fa.md`: نصب، Cron، بکاپ و ارتقا روی cPanel.
- `docs/adr/`: تصمیم‌های معماری ثبت‌شده.

## قواعد محصول

هر کلینیک یک Tenant مستقل است. دادهٔ Tenant از ورودی خام کاربر resolve نمی‌شود و باید از Context معتبر، عضویت یا Support Access سوپرادمین به‌دست آید. حذف پیش‌فرض بایگانی است. دسترسی‌های View/Create/Edit/Archive/Print/Export در سمت سرور Policy می‌شوند و UI فقط بازتاب آن‌ها است.

## امنیت و استقرار

در تولید HTTPS اجباری است. `.env`، کلیدها، Log و فایل‌های پزشکی نباید در Git یا public قرار گیرند. پیش از هر ارتقا باید بکاپ کامل گرفته شود و شکست Migration باید مسیر بازیابی داشته باشد. جزئیات خطای فنی به بیمار یا کارمند نمایش داده نمی‌شود و فقط کد پیگیری فارسی ارائه می‌گردد.

## مالکیت و برند

برند محصول در تنظیمات سوپرادمین قابل تغییر است. نام مالک اعلام‌شدهٔ پروژه، شرکت Disweb با نشانی `disweb.ir` است. مجوز وابستگی‌ها و دارایی‌های فونت باید در تحویل نهایی مستند شوند.
