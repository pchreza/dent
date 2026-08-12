# چک‌لیست QA فاز پورتال بیمار

| شناسه | حوزه | معیار | وضعیت |
|---|---|---|---|
| AUTH-01 | فعال‌سازی | تأیید QR حساب User، نقش patient، membership و patient_user را می‌سازد. | تست Feature موفق |
| AUTH-02 | ورود | حساب بیمار پس از ورود به پورتال خودش redirect می‌شود. | تست Feature موفق |
| AUTH-03 | رمز | رمز اولیه hash می‌شود و تغییر رمز اجباری پیش از مشاهدهٔ اطلاعات اعمال می‌شود. | تست Feature موفق |
| TENANT-01 | جداسازی | بیمار فقط بین PatientAccountهای خودش می‌تواند Tenant فعال را انتخاب کند. | تست چندمستاجری موفق |
| TENANT-02 | جلوگیری از نشت | بیمار از routeهای پنل کارکنان به پورتال خودش redirect می‌شود. | تست Feature موفق |
| DATA-01 | نوبت | فقط Appointmentهای همان Tenant و Patient نمایش داده می‌شوند. | تست Feature و بازبینی موفق |
| DATA-02 | مالی | فقط Invoiceهای همان Tenant و Patient نمایش داده می‌شوند. | تست Feature و بازبینی موفق |
| DATA-03 | درمان | طرح درمان بیمار به‌صورت read-only با وضعیت و تاریخ شمسی نمایش داده می‌شود. | بازبینی موفق |
| RTL-01 | رابط | پوستهٔ مستقل پورتال فارسی، RTL و Vazirmatn آفلاین است. | بازبینی موفق |
| RTL-02 | دادهٔ ترکیبی | تاریخ، ساعت، مبلغ، شماره فاکتور و شناسه با bdi/dir=ltr ایزوله‌اند. | بازبینی موفق |
| MOBILE-01 | responsive | عرض‌های 375 و 768 بدون overflow افقی render می‌شوند. | smoke موفق |
| A11Y-01 | Drawer | aria state، focus انتقالی، Tab loop، Escape و focus return موفق است. | smoke موفق |
| REG-01 | regression | تست‌های موجود و تست‌های پورتال بدون شکست اجرا می‌شوند. | موفق — ۴۳ تست و ۱۸۳ assertion، Pint روی ۱۳۶ فایل، Composer validate/audit، Blade cache، Vite build و diff check |
| RELEASE-01 | بسته | Release بدون vendor، node_modules، .env و دادهٔ محلی ساخته می‌شود. | پس از Commit و package اجرا می‌شود |

## موارد خارج از این فاز

آزمون دستی screen reader، OTP/SMS واقعی، پرداخت آنلاین، ویرایش کامل پرونده توسط بیمار و بازیابی رمز از کانال بیرونی در این فاز اجرا نمی‌شوند و به backlog فازهای بعد منتقل شده‌اند.
