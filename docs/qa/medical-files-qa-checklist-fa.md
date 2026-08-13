# چک‌لیست QA فاز فایل‌های پزشکی

| شناسه | حوزه | معیار پذیرش | وضعیت |
|---|---|---|---|
| MF-01 | migration | جدول `file_assets` با Tenant، owner polymorphic، disk خصوصی، metadata و `deleted_at` ایجاد می‌شود. | موفق |
| MF-02 | upload | مدیر مجاز می‌تواند JPG/PNG معتبر زیر ۱ مگابایت را آپلود کند و رکورد/Storage/Audit ساخته می‌شود. | موفق |
| MF-03 | MIME | PDF، متن و تصویر با محتوای جعلی حتی با extension مجاز رد می‌شوند. | موفق |
| MF-04 | حجم | فایل بزرگ‌تر از ۱ مگابایت با validation فارسی رد و بدون رکورد ذخیره می‌شود. | موفق |
| MF-05 | path | نام ورودی در path استفاده نمی‌شود و نام تصادفی UUID در مسیر خصوصی ساخته می‌شود. | موفق |
| MF-06 | download | دانلود با Tenant، Patient، FileAsset فعال و Disk `local` resolve می‌شود و URL عمومی ندارد. | موفق |
| MF-07 | IDOR | شناسهٔ فایل Tenant دیگر با HTTP مستقیم قابل دریافت یا archive نیست. | موفق |
| MF-08 | archive | archive نرم، `deleted_at` و Audit را ثبت می‌کند؛ فایل از فهرست حذف و download آن مسدود می‌شود. | موفق |
| MF-09 | permissions | `clinical_files.view/create/archive` در middleware و Request سمت سرور اعمال می‌شوند. | موفق — تست HTTP مستقیم بدون create |
| MF-10 | Audit | upload، download و archive با actor، Tenant و metadata غیرحساس ثبت می‌شوند. | موفق |
| MF-11 | پرونده | کارت فایل‌های پزشکی با فرم multipart، دسته، عنوان، empty state و فهرست uploader render می‌شود. | موفق — Feature و مرورگر |
| MF-12 | RTL | labelها، پیام‌ها، کارت و عملیات از Design System فارسی RTL و Vazirmatn پیروی می‌کنند. | موفق — بازبینی مستقیم |
| MF-13 | responsive | پرونده در عرض‌های ۳۷۵ و ۷۶۸ بدون overflow افقی render می‌شود. | موفق — smoke authenticated |
| MF-14 | Drawer | aria، focus، Tab loop، Escape و focus return در پوستهٔ پنل حفظ می‌شود. | موفق — smoke authenticated |
| MF-15 | regression | ۵۵ تست، ۲۵۷ assertion، Pint روی ۱۴۷ فایل، Composer validate/audit، Blade cache، Vite build و diff check موفق هستند. | موفق |
| MF-16 | release | ZIP بدون vendor، node_modules، `.env`، Log، Cache و دادهٔ محلی ساخته و روی نصب تمیز smoke می‌شود. | در انتظار ساخت Release |

## ریسک‌ها و محدودیت‌های باقی‌مانده

پاک‌سازی EXIF، preview امن تصویر، retention job برای حذف فیزیکی، اشتراک کنترل‌شده با بیمار، consent، watermark، object storage/S3 و antivirus scanning در این فاز پیاده‌سازی نشده‌اند و باید پیش از فعال‌سازی سناریوهای بیمار یا حجم بالا در فازهای بعدی طراحی شوند.
