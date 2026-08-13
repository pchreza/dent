# برنامهٔ فاز ۹ — مدیریت امن فایل‌های پزشکی

## محدوده و baseline مجاز

این فاز روی پروژهٔ مجاز کاربر در `/home/ubuntu/dent`، شاخهٔ `main` و محیط محلی/fixture تستی اجرا می‌شود. هیچ probing یا تغییری روی هاست production یا دادهٔ واقعی بیمار انجام نمی‌شود. rollback با برگشت commit، Release قبلی، backup دیتابیس و backup Storage انجام خواهد شد.

در نسخهٔ فعلی پروندهٔ بیمار، حساسیت‌ها، داروها، سوابق، فیلدهای سفارشی، وضعیت دندان و طرح درمان وجود دارند، اما هیچ جدول، مدل، route یا handler آپلود فایل پزشکی وجود ندارد. Filesystem پیش‌فرض Laravel روی Disk خصوصی `local` در `storage/app/private` تنظیم است و Disk عمومی برای این دادهٔ حساس نباید استفاده شود.

## کنترل‌های اجباری امنیتی

قرارداد امنیتی موجود JPG/PNG با سقف ۱ مگابایت، بررسی MIME واقعی، نام تصادفی، مسیر خصوصی و download پس از بررسی Tenant، بیمار، نوع فایل و مجوز را الزام می‌کند. فایل نامعتبر، بزرگ، MIME جعلی، نام traversal، ID فایل Tenant دیگر و route مستقیم بدون مجوز باید در تست Feature رد شوند.

| کنترل | تصمیم فاز ۹ |
|---|---|
| نوع | فقط محتوای image معتبر با JPG/JPEG/PNG؛ extension یا MIME اعلامی کاربر به‌تنهایی قابل اعتماد نیست. |
| حجم | حداکثر ۱۰۲۴ کیلوبایت در validation سمت سرور و کنترل دوم قبل از ذخیره. |
| نام | نام اصلی برای نمایش در metadata پاک‌سازی می‌شود؛ path با UUID و بدون ورودی کاربر ساخته می‌شود. |
| محل | Disk خصوصی `local`، بدون symlink عمومی و بدون URL مستقیم. |
| مالکیت | `tenant_id` به‌علاوهٔ owner polymorphic از نوع `Patient`; دسترسی همیشه با Tenant فعال و بیمار جاری کنترل می‌شود. |
| دانلود | `Storage::download` پس از authorization؛ پاسخ inline یا public URL ساخته نمی‌شود. |
| حذف | archive نرم با `deleted_at` و حفظ metadata برای Audit؛ حذف فیزیکی در این فاز انجام نمی‌شود. |
| ممیزی | upload، download و archive با Tenant، actor، file asset و علت در Audit ثبت می‌شوند. |
| خطا | پیام فارسی عمومی؛ path، MIME داخلی، stack trace یا metadata حساس به کاربر برنمی‌گردد. |

## مجوزها

برای جلوگیری از اتکا به UI، سه مجوز مستقل در seeder اضافه می‌شود: `clinical_files.view` برای مشاهده و دانلود، `clinical_files.create` برای آپلود و `clinical_files.archive` برای archive. مدیر کلینیک به‌دلیل مجوز `all` همه را دارد. پزشک به مشاهده و آپلود دسترسی خواهد داشت؛ archive فقط برای مدیر کلینیک/کاربر دارای مجوز اختصاصی فعال است. بیمار، routeهای فایل کارکنان و دادهٔ فایل پزشکی را در پورتال read-only مشاهده نمی‌کند.

## موجودیت داده

جدول `file_assets` طبق قرارداد معماری شامل `tenant_id`، `owner_type`، `owner_id`، `disk`، `path`، `mime_type`، `size`، `metadata_json` و `deleted_at` است و برای نیاز عملیاتی فاز، `category`، `uploaded_by` و timestamp نیز دارد. `metadata_json` شامل نام پاک‌سازی‌شده، عنوان اختیاری، ابعاد تصویر و checksum است؛ مسیر واقعی هرگز در View یا URL عمومی چاپ نمی‌شود.

## جریان‌های کاربر

کاربر مجاز از صفحهٔ پروندهٔ بیمار فایل را با عنوان و دستهٔ «تصویر رادیولوژی»، «تصویر داخل دهان» یا «سایر» انتخاب می‌کند. پس از validation و ذخیرهٔ خصوصی، کارت فایل با نوع، حجم، تاریخ شمسی، uploader و عملیات مجاز نمایش داده می‌شود. دانلود یک response امن با همان scope است و archive نرم فقط با مجوز مستقل و CSRF انجام می‌شود.

پورتال بیمار در این فاز تغییر نمی‌کند؛ نمایش یا اشتراک فایل برای بیمار به فاز آتی نیاز دارد چون نیازمند policy محصول، consent، expiry و watermark جداگانه است.

## معیار پذیرش

فاز فقط وقتی کامل است که migration fresh، upload موفق JPG/PNG، رد MIME جعلی و فایل بزرگ، Tenant isolation، IDOR فایل، permission مستقیم HTTP، archive نرم، download خصوصی، Audit، صفحهٔ پروندهٔ RTL، responsive در ۳۷۵/۷۶۸ و Quality Gate کامل موفق باشند. ZIP Release نباید `storage/app/private` با دادهٔ محلی، `.env`، log یا فایل fixture واقعی را منتشر کند.

## طراحی فنی تأییدشده

| جزء | مسئولیت | قرارداد |
|---|---|---|
| migration `create_file_assets_table` | metadata و مالکیت فایل | index ترکیبی `tenant_id, owner_type, owner_id, deleted_at`؛ foreign key برای uploader؛ rollback فقط table را حذف می‌کند. |
| مدل `FileAsset` | metadata، relationهای Tenant/actor/owner و scope active | `metadata` به array cast می‌شود و soft archive با `deleted_at` انجام می‌شود. |
| `StoreMedicalFileRequest` | validation فرم multipart | `image` فقط JPG/JPEG/PNG و max 1024KB؛ category allowlist؛ title اختیاری؛ MIME و image decode سمت سرور. |
| `MedicalFileController` | upload، download و archive | بیمار را فقط از Tenant فعال دریافت می‌کند؛ فایل را هر بار با `tenant_id`، `owner_type=Patient` و `deleted_at=null` resolve می‌کند. |
| `MedicalFileStorage` | نام تصادفی، ذخیره خصوصی و metadata | Disk `local`، UUID، extension استنتاج‌شده، SHA-256، ابعاد image و نام نمایشی پاک‌سازی‌شده. |
| routeها | سطح دسترسی HTTP | مشاهده/download: `clinical_files.view`، upload: `clinical_files.create`، archive: `clinical_files.archive`. |
| View پرونده | فرم، فهرست و عملیات مجاز | input قابل دسترس، پیام فارسی، نام/path امن، تاریخ شمسی و responsive logical CSS. |

### قرارداد route

مسیرهای جدید زیر مجموعهٔ `clinic` و middlewareهای موجود `auth`, `tenant`, `staff_portal` هستند. `POST /clinic/patients/{patientId}/medical-files` برای upload، `GET /clinic/patients/{patientId}/medical-files/{fileId}/download` برای download و `DELETE /clinic/patients/{patientId}/medical-files/{fileId}` برای archive تعریف می‌شوند. Constraint عددی روی هر شناسه اعمال می‌شود و routeها قبل از اجرا permission middleware می‌گیرند.

### تصمیم‌های ذخیره‌سازی

فایل در مسیر منطقی `medical/{tenantId}/{patientId}/{uuid}.{extension}` در disk خصوصی ذخیره می‌شود. `uuid` فقط از سرور می‌آید و extension از فایل validate‌شده گرفته می‌شود. title و category در metadata نمایش داده می‌شوند؛ نام اصلی کاربر فقط بعد از پاک‌سازی در metadata باقی می‌ماند. URL عمومی، symlink، `public` disk، move به مسیر قابل اجرا یا استفاده از نام اصلی به‌عنوان path ممنوع است.

در صورت خطای database بعد از ذخیره، فایل تازه ذخیره‌شده حذف می‌شود تا orphan file ایجاد نشود. در صورت خطای storage قبل از create metadata، هیچ رکوردی ایجاد نمی‌شود. archive در این فاز فقط metadata را soft-delete می‌کند و فایل فیزیکی را برای امکان سیاست retention بعدی نگه می‌دارد؛ دانلود فایل archived مسدود می‌شود.

### قرارداد View و دسترس‌پذیری

کارت «فایل‌های پزشکی» در پروندهٔ بیمار قرار می‌گیرد و وابسته به وجود یا نبود سایر بخش‌های پرونده نیست. فرم فقط وقتی `clinical_files.create` مجاز باشد ظاهر می‌شود. فهرست فایل‌ها برای `clinical_files.view` قابل مشاهده است؛ دکمهٔ archive فقط با مجوز اختصاصی نمایش داده می‌شود. اطلاعات mixed-direction شامل checksum یا اندازه با `bdi dir=ltr` ایزوله می‌شوند. نوع فایل هرگز فقط با رنگ مشخص نمی‌شود و دکمه‌های icon-only در صورت استفاده aria-label فارسی دارند.

### ماتریس تست طراحی

| سناریو | انتظار |
|---|---|
| JPG معتبر زیر ۱MB | فایل خصوصی، metadata، Audit و card فهرست ایجاد می‌شود. |
| PNG معتبر زیر ۱MB | همان رفتار JPG. |
| PDF/متن/فایل با MIME جعلی | validation فارسی، عدم ایجاد database record و عدم ذخیره فایل. |
| فایل بزرگ | validation فارسی و عدم ذخیره. |
| عنوان/path مخرب | path تولیدی UUID است و نام ورودی به path راه ندارد. |
| فایل Tenant دیگر | download/archive پاسخ 404 یا 403 امن می‌گیرد و data نشت نمی‌کند. |
| کاربر فاقد مجوز | POST/GET/DELETE مستقیم HTTP مسدود است. |
| archive | `deleted_at` ثبت، فایل در فهرست حذف و download مسدود می‌شود. |
| Audit | upload/download/archive با اطلاعات غیرحساس metadata ثبت می‌شود. |
| UI | RTL، Vazirmatn، ۳۷۵/۷۶۸، keyboard و بدون overflow افقی. |
