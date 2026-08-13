# چک‌لیست QA فاز مرکز گزارش

| شناسه | حوزه | معیار پذیرش | وضعیت |
|---|---|---|---|
| RPT-01 | فهرست | پنج گزارش اصلی بیماران، نوبت‌ها، طرح درمان، مالی و خدمات در مرکز گزارش نمایش داده می‌شوند. | موفق |
| RPT-02 | authorization | ورود به مرکز به `reports.view` و هر گزارش به مجوز ماژول دادهٔ مربوطه نیاز دارد. | موفق — تست 403 و least-privilege |
| RPT-03 | export | CSV فقط با `reports.export` در دسترس است و سقف ۵۰۰۰ ردیف دارد. | موفق |
| RPT-04 | Tenant | Query، KPI، جدول، چاپ و CSV با Tenant فعال محدود می‌شوند. | موفق — تست جداسازی داده |
| RPT-05 | بیماران | فیلتر تاریخ ایجاد، وضعیت، جست‌وجوی بیمار و KPIهای فعال/بایگانی‌شده کار می‌کند. | موفق |
| RPT-06 | نوبت‌ها | فیلتر تاریخ، وضعیت، شعبه، پزشک و KPIهای تکمیل/لغو/عدم حضور کار می‌کند. | موفق |
| RPT-07 | درمان | فیلتر وضعیت و خدمت و KPIهای پیشرفت و مبلغ برآوردی کار می‌کند. | موفق |
| RPT-08 | مالی | فیلتر تاریخ صدور، وضعیت، روش پرداخت، جمع کل، وصول و مانده درست است. | موفق — fixture مالی |
| RPT-09 | خدمات | ردیف‌های invoice item با فیلتر خدمت، تعداد فاکتور و مبلغ جمع می‌شوند. | موفق |
| RPT-10 | تاریخ | تاریخ شمسی معتبر به بازهٔ Gregorian درست تبدیل و تاریخ نامعتبر با پیام فارسی رد می‌شود. | موفق — round-trip و تست validation |
| RPT-11 | CSV | UTF-8 BOM، header فارسی، escaping و formula injection protection وجود دارد. | موفق — تست `'=SUM(1,1)` |
| RPT-12 | چاپ | print route، عنوان، Tenant، بازه، زمان تولید و stylesheet چاپ وجود دارد. | موفق — Feature و مرورگر |
| RPT-13 | RTL | Layout، breadcrumb، filter bar، KPI و table با `dir=rtl` و داده‌های LTR ایزوله هستند. | موفق — بازبینی مستقیم |
| RPT-14 | responsive | عرض‌های ۳۷۵ و ۷۶۸ بدون overflow افقی render می‌شوند. | موفق — smoke authenticated |
| RPT-15 | Drawer | aria state، focus، Tab loop، Escape و focus return در پنل گزارش حفظ می‌شود. | موفق — smoke authenticated |
| RPT-16 | regression | تست کامل، Pint، Composer، Blade cache، Vite build و diff check باید قبل از release موفق شوند. | موفق — ۵۰ تست و ۲۱۵ assertion، Pint روی ۱۴۱ فایل، Composer validate/audit، Blade cache، Vite build و diff check |

## موارد خارج از فاز

گزارش‌ساز قابل تنظیم توسط کلینیک، export پس‌زمینه با `export_jobs`، Excel باینری، زمان‌بندی گزارش، ارسال Email/SMS، نمودارهای تحلیلی پیشرفته و global reporting در این فاز ساخته نشده‌اند.
