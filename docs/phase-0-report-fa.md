# گزارش تکمیل فاز صفر

**پروژه:** Disweb Dental SaaS

**نسخه:** 1.0

**وضعیت:** فاز صفر تکمیل شد؛ مخزن برای شروع فاز یک آماده است.

## 1. خروجی‌های ایجادشده

| فایل | محتوا |
|---|---|
| `docs/architecture/phase-0-architecture-fa.md` | فناوری، لایه‌ها، ماژول‌ها، Tenant، نصب و قرارداد کیفیت. |
| `docs/architecture/domain-model-fa.md` | موجودیت‌ها، فیلدهای کلیدی، روابط و ایندکس‌های اولیه. |
| `docs/product/mvp-requirements-fa.md` | نقش‌ها، جریان‌های حیاتی، محدوده MVP و معیارهای پذیرش. |
| `docs/security/threat-model-fa.md` | دارایی‌ها، مرز اعتماد، تهدیدها و کنترل‌های امنیتی. |
| `docs/qa/phase-0-test-strategy-fa.md` | هرم تست، گیت‌ها، تعریف Done و سناریوهای پذیرش. |
| `docs/ux/rtl-design-spec-fa.md` | Design System، RTL، فونت، دسترس‌پذیری و ماتریس QA. |
| `docs/ux/design-system-generated.md` | خروجی Design System اولیهٔ محصول سلامت/دندان‌پزشکی. |
| `docs/operations/cpanel-deployment-fa.md` | نصب cPanel، Cron، بکاپ و ارتقا. |

## 2. تصمیم‌های قطعی

- PHP 8.3 و Laravel با Blade/Livewire/Alpine.
- MySQL 8 و معماری مونولیت ماژولار.
- یک نصب مرکزی روی cPanel با Tenantهای مستقل.
- `tenant_id` و Tenant Context اجباری برای داده‌های کلینیک.
- فونت Vazirmatn آفلاین و رابط فارسی/RTL.
- MVP شامل هستهٔ دسترسی، QR/پرونده، نمودار دندان/طرح درمان، تقویم/نوبت، فاکتور/پرداخت ساده و اعلان داخلی.
- IPPanel، درگاه آنلاین، PWA، API، CSV Import و ارتباط خارجی در فازهای بعد.
- بایگانی پیش‌فرض و حذف دائمی محدود با ممیزی.
- تست Cross-Tenant و مجوز در هر فاز اجباری.

## 3. موارد خارج از فاز صفر

در فاز صفر قابلیت کاربردی و صفحهٔ نهایی ساخته نشده است. پیاده‌سازی Migration، مدل‌های واقعی، ویزارد نصب، احراز هویت، UI و تست‌های اجرایی از فاز یک شروع می‌شوند. این تفکیک برای جلوگیری از ساخت قابلیت‌هایی است که هنوز قرارداد فنی آن‌ها ثبت نشده باشد.

## 4. Backlog فاز یک

### گروه A — اسکلت و نصب

- ساخت `composer.json` و پروژهٔ Laravel.
- افزودن تنظیمات strict و PSR-12.
- ایجاد ساختار ماژولار و قراردادهای Tenant.
- ساخت Migrationهای هسته: tenants، users، memberships، roles، permissions، audits، settings.
- Seed نقش‌ها و مجوزهای پایه.
- ساخت ویزارد نصب و Lock.
- ساخت خطای فارسی با tracking code.

### گروه B — امنیت پایه

- Session و Login/Logout.
- Password policy و تغییر رمز اولیه.
- Rate limit ورود و عملیات حساس.
- CSRF/XSS/SQLi guardrails.
- Audit service.
- تست‌های Tenant isolation و Permission.

### گروه C — تست و مستندات فاز یک

- Unit تست Scope/Permission/Tracking code.
- Feature تست نصب و ورود.
- تست منفی Cross-Tenant.
- تست PHPStan/Pint/Pest یا PHPUnit.
- گزارش تست فاز یک و دستور نصب توسعه.

## 5. معیار آغاز فاز یک

فاز صفر از نظر مستندات تکمیل شده است. مخزن GitHub در زمان شروع خالی بود و ساختار مستندات در آن ایجاد شده است. فاز یک می‌تواند با ایجاد اسکلت Laravel و Commit اولیه آغاز شود.

## 6. ریسک ثبت‌شده

محیط توسعهٔ اولیه PHP نداشت؛ PHP 8.3 و افزونه‌های لازم برای اجرای تست‌های بعدی نصب شدند. این نصب فقط برای محیط توسعه است و در بستهٔ نهایی باید سازگاری با PHP 8.2/8.3 cPanel در ویزارد بررسی شود.
