## یافتهٔ اولیهٔ محیط محلی

در بازبینی مرورگر، فهرست بیماران با موفقیت باز شد، اما ورود به پروندهٔ بیمار موجود در SQLite توسعه با خطای ۵۰۰ متوقف شد: جدول `file_assets` در دیتابیس فعلی هنوز ایجاد نشده بود. این یک وضعیت migration محیط محلی است، نه خطای query در تست‌های Feature؛ تست‌های `RefreshDatabase` با migration جدید موفق بودند. اقدام اصلاحی کنترل‌شده: اجرای `php artisan migrate --force` فقط روی SQLite محلی sandbox، سپس تکرار بازبینی.
## بازبینی دسکتاپ پرونده

پس از اجرای migration `file_assets` و seed مجوزها در SQLite محلی، پروندهٔ بیمار با موفقیت render شد. کارت «فایل‌های پزشکی» با empty state، فرم multipart، فیلد انتخاب فایل، دستهٔ فارسی، عنوان اختیاری، محدودیت واضح ۱ مگابایت و پیام ذخیرهٔ خصوصی نمایش داده شد. ساختار RTL و Design System نسخهٔ ۰.۵.۰ حفظ شده و هیچ URL عمومی فایل در View وجود ندارد.

فرم از نظر متن‌های فارسی، labelهای قابل اتصال به input و ترتیب منطقی کنترل‌ها مناسب است. عملیات upload واقعی در بازبینی مرورگر انجام نشد تا دادهٔ fixture محلی بدون نیاز باقی بماند؛ مسیر و رفتار آن با تست Feature و Storage fake پوشش داده شده است.
## Responsive و Drawer

Responsive smoke authenticated روی `/clinic/patients/1` در `375×812` و `768×1024` موفق بود. در هر دو viewport، `scrollWidth` برابر عرض viewport و `hasHorizontalOverflow=false` ثبت شد. Drawer با aria state صحیح باز و بسته شد، focus به دکمهٔ بستن منتقل شد، Tab loop با ۱۳ کنترل قابل فوکوس حفظ شد و Escape با focus return به دکمهٔ بازکردن ناوبری عمل کرد.
