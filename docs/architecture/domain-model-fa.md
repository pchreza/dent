# مدل دامنه و طرح اولیهٔ داده — نسخهٔ MVP

این سند مدل مفهومی است و پیش از هر Migration نهایی باید با قواعد Laravel و تست‌های دامنه هم‌تراز شود. تمام شناسه‌های عملیاتی با ULID/UUID قابل تنظیم طراحی می‌شوند؛ شمارهٔ پرونده و فاکتور شمارهٔ نمایشی قابل جست‌وجو هستند و نباید تنها کلید داده باشند.

## 1. موجودیت‌های زیرساختی

| موجودیت | فیلدهای کلیدی | قاعده |
|---|---|---|
| tenants | id, code, name, status, plan_id, starts_at, ends_at, branding | کد یکتا؛ وضعیت چرخهٔ عمر؛ مرز اصلی داده. |
| users | id, mobile, username, password_hash, status, last_login_at | کاربر می‌تواند چند عضویت داشته باشد. |
| tenant_user | tenant_id, user_id, role_id, branch_id, scope_json, status | نقش و محدودهٔ دسترسی در هر Tenant. |
| roles | id, tenant_id nullable, name, code, is_system | نقش سراسری یا سفارشی کلینیک. |
| permissions | id, module, action, code | فهرست سراسری و نسخه‌پذیر. |
| role_permissions | role_id, permission_id, allowed | نقش به مجوز. |
| user_permissions | tenant_user_id, permission_id, allowed | استثناء مجوز شخصی. |
| audit_events | id, tenant_id nullable, actor_id, action, subject_type, subject_id, before_json, after_json, reason, ip, user_agent, created_at | غیرقابل حذف برای رویدادهای حساس. |
| notifications | id, tenant_id, recipient_id, type, title, body, status, action_url, read_at, expires_at | مرکز اعلان شخصی. |

## 2. ساختار کلینیک

| موجودیت | فیلدهای کلیدی |
|---|---|
| clinics | tenant_id, display_name, logo_path, primary_color, secondary_color, phone, address, invoice_settings_json |
| branches | tenant_id, name, code, address, phone, is_active |
| practitioners | tenant_id, user_id, license_no, specialty, is_active |
| clinic_staff | tenant_id, user_id, staff_type, is_active |
| rooms | tenant_id, branch_id, name, code, is_active |
| units | tenant_id, branch_id, room_id, name, code, unit_type, is_active |
| working_hours | tenant_id, scope_type, scope_id, weekday, shift, starts_at, ends_at |
| calendar_exceptions | tenant_id, scope_type, scope_id, date, type, reason, starts_at, ends_at |
| clinic_settings | tenant_id, key, value_json, updated_by |

`scope_type` برای ساعت و استثنا می‌تواند `clinic`, `branch`, `practitioner`, `room`, `unit` باشد. حل تعارض از سطح خاص‌تر به عمومی‌تر انجام می‌شود و نتیجه باید قابل توضیح در UI باشد.

## 3. بیمار و پرونده

| موجودیت | فیلدهای کلیدی |
|---|---|
| patients | tenant_id, patient_no, first_name, last_name, national_id, mobile, birth_date, gender, status, verified_at |
| patient_profiles | patient_id, phone, address, insurance_name, emergency_contact_json, custom_fields_json |
| patient_medical_conditions | tenant_id, patient_id, condition_id, value, note, recorded_by |
| medical_condition_definitions | tenant_id nullable, name, code, is_system, is_active |
| patient_allergies | tenant_id, patient_id, substance_name, reaction, severity, note, is_critical, recorded_by |
| patient_medications | tenant_id, patient_id, name, dosage, frequency, duration, instruction, is_active |
| patient_notes | tenant_id, patient_id, author_id, visibility, body, created_at |
| patient_change_requests | tenant_id, patient_id, requested_by, payload_json, status, reviewed_by, reason |
| qr_registration_requests | tenant_id, token_hash, payload_json, duplicate_match_json, status, reviewed_by, reviewed_at |

شمارهٔ موبایل و کد ملی در محدودهٔ Tenant با ایندکس مناسب یکتا/قابل جست‌وجو می‌شوند. تطبیق چندکلینیکی باید به‌صورت محدود و فقط با دادهٔ لازم انجام شود و نباید پروندهٔ درمانی کلینیک دیگر را نمایش دهد.

## 4. نمودار دندان و درمان

| موجودیت | فیلدهای کلیدی |
|---|---|
| dentition_types | code, name, is_system |
| teeth | tenant_id, patient_id, dentition_type, fdi_code, tooth_label, congenital_status |
| tooth_surfaces | tooth_id, surface_code, status_code, note |
| tooth_status_definitions | tenant_id nullable, code, name, color, severity, is_active |
| tooth_annotations | tenant_id, tooth_id, author_id, type, geometry_json, note, status |
| services | tenant_id, category_id, code, name, description, default_duration, sessions_count, base_price, calendar_color, is_active |
| service_steps | service_id, sort_order, name, is_required, checklist_json, status |
| treatment_plans | tenant_id, patient_id, title, version, status, is_active, created_by, reason |
| treatment_plan_items | treatment_plan_id, tooth_id, service_id, practitioner_id, branch_id, quantity, duration, captured_price, status, note |
| treatment_item_events | tenant_id, item_id, from_status, to_status, reason, actor_id, created_at |

`captured_price` و زمان ثبت در آیتم طرح درمان immutable هستند. تغییر قیمت خدمت فقط روی موارد آینده اثر دارد. وضعیت‌های دندان و خدمت به‌صورت Tenant-aware قابل غیرفعال‌سازی هستند اما رکوردهای قبلی را حذف نمی‌کنند.

## 5. تقویم، نوبت و پذیرش

| موجودیت | فیلدهای کلیدی |
|---|---|
| appointments | tenant_id, patient_id, practitioner_id, branch_id, room_id, unit_id, service_id, starts_at, ends_at, status, source, is_emergency, note |
| appointment_series | tenant_id, rule_json, starts_at, ends_at, count, ends_on |
| appointment_occurrences | series_id, appointment_id, occurrence_date, override_json |
| waitlist_entries | tenant_id, patient_id, practitioner_id nullable, service_id nullable, preferred_range_json, status |
| treatment_sessions | tenant_id, patient_id, appointment_id, started_at, stopped_at, duration_seconds, recorded_by |
| checkins | tenant_id, appointment_id, arrived_at, delayed_minutes, no_show_reason, room_transferred_at |

تداخل باید در یک سرویس واحد محاسبه شود و منابع غیرقابل هم‌پوشانی را بررسی کند. استثناء دو بیمار نزد یک پزشک فقط با سیاست ثبت‌شدهٔ کلینیک و اتصال به یونیت مجاز است.

## 6. مالی

| موجودیت | فیلدهای کلیدی |
|---|---|
| invoices | tenant_id, patient_id, invoice_no, status, subtotal, discount_amount, total, paid_amount, balance, currency, issued_at |
| invoice_items | invoice_id, service_id, treatment_item_id, description, quantity, captured_unit_price, total |
| payments | tenant_id, patient_id, invoice_id, amount, method, tracking_no, received_by, paid_at, note |
| payment_allocations | payment_id, invoice_id, invoice_item_id nullable, amount |
| installments | tenant_id, invoice_id, amount, due_date, status, paid_at, note |
| wallets | tenant_id, patient_id, balance |
| wallet_transactions | tenant_id, wallet_id, type, amount, reference_type, reference_id, created_by |
| discounts | tenant_id, invoice_id, type, amount, approved_by, reason |
| financial_adjustments | tenant_id, invoice_id, type, amount, reason, approved_by |

جمع حساب از تراکنش‌ها و فاکتورها قابل بازسازی است. پرداخت اضافه نباید با تغییر دستی فاکتور ناپدید شود؛ باید به‌صورت تراکنش کیف پول ثبت شود.

## 7. فایل، فرم و گزارش

| موجودیت | فیلدهای کلیدی |
|---|---|
| custom_form_definitions | tenant_id, name, schema_json, version, is_active |
| custom_form_submissions | tenant_id, form_id, patient_id, payload_json, submitted_by |
| file_assets | tenant_id, owner_type, owner_id, disk, path, mime_type, size, metadata_json, deleted_at |
| report_definitions | tenant_id nullable, code, filters_json, is_active |
| export_jobs | tenant_id, requested_by, type, filters_json, status, path, expires_at |
| backups | tenant_id nullable, type, path, size, status, checksum, created_by, expires_at |

فایل‌های MVP طبق تصمیم کاربر JPG/PNG تا سقف ۱ مگابایت هستند. `file_assets` متادیتای حذف‌شده را برای ممیزی نگه می‌دارد و مسیر واقعی فایل عمومی نیست.

## 8. ایندکس‌های کلیدی

- `patients (tenant_id, national_id)`
- `patients (tenant_id, mobile)`
- `patients (tenant_id, status, last_name, first_name)`
- `appointments (tenant_id, starts_at, practitioner_id, status)`
- `appointments (tenant_id, starts_at, unit_id, status)`
- `invoices (tenant_id, patient_id, status, issued_at)`
- `audit_events (tenant_id, created_at, actor_id)`
- `notifications (recipient_id, status, created_at)`

ایندکس‌ها پس از ساخت Queryهای واقعی و تست دادهٔ بزرگ اندازه‌گیری می‌شوند؛ ایندکس بی‌دلیل یا `SELECT *` در فهرست‌های بزرگ مجاز نیست.
