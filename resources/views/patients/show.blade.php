@extends('layouts.app', ['title' => 'پروندهٔ بیمار'])

@section('content')
@php($canEditClinical = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'clinical.update'))
@php($canViewDentalChart = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'dentistry.view'))
@php($canUpdateTreatments = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'treatments.update'))
@php($canViewMedicalFiles = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'clinical_files.view'))
@php($canCreateMedicalFiles = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'clinical_files.create'))
@php($canArchiveMedicalFiles = auth()->check() && app(\App\Support\AuthorizationService::class)->allows(auth()->user(), 'clinical_files.archive'))
@php($medicalFileCategories = ['xray' => 'تصویر رادیولوژی', 'intraoral_photo' => 'تصویر داخل دهان', 'other' => 'سایر'])
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }} · پروندهٔ {{ $patient->patient_no }}</span>
        <h1>{{ $patient->fullName() }}</h1>
        <p class="muted">آخرین به‌روزرسانی: <span dir="ltr"><bdi>{{ $patient->updated_at ? \App\Support\JalaliDate::format($patient->updated_at).' · '.$patient->updated_at->format('H:i') : '—' }}</bdi></span></p>
    </div>
    <div class="inline-actions">
        @if ($canEditClinical)
            <a class="button button--secondary" href="{{ route('clinical-fields.index') }}">تنظیم فیلدهای پرونده</a>
        @endif
        @if ($canViewDentalChart)
            <a class="button button--secondary" href="{{ route('dental-chart.show', ['patientId' => $patient->id]) }}">وضعیت دندان‌ها</a>
        @endif
        <a class="button button--primary" href="{{ route('treatment-plans.create', ['patientId' => $patient->id]) }}">ایجاد طرح درمان</a>
        <a class="button button--ghost" href="{{ route('patients.index') }}">بازگشت به بیماران</a>
    </div>
</div>

@if ($patient->hasCriticalAllergy())
    <div class="critical-alert" role="alert"><strong>هشدار حساسیت بحرانی:</strong> پیش از هر اقدام درمانی حساسیت‌های بیمار را بررسی کنید.</div>
@endif

<section class="patient-overview-grid">
    <article class="card"><span class="metric-card__label">موبایل</span><strong dir="ltr"><bdi>{{ $patient->mobile }}</bdi></strong><small>{{ $patient->insurance_name ?: 'بیمه ثبت نشده' }}</small></article>
    <article class="card"><span class="metric-card__label">کد ملی</span><strong dir="ltr"><bdi>{{ $patient->national_id }}</bdi></strong><small>{{ $patient->birth_date ? \App\Support\JalaliDate::format($patient->birth_date) : 'تاریخ تولد ثبت نشده' }}</small></article>
    <article class="card"><span class="metric-card__label">وضعیت پرونده</span><strong>{{ ['active' => 'فعال', 'pending' => 'در انتظار بررسی', 'inactive' => 'غیرفعال', 'archived' => 'بایگانی‌شده'][$patient->status] ?? $patient->status }}</strong><small>{{ $patient->verified_at ? 'تأییدشده' : 'در انتظار تأیید' }}</small></article>
</section>

@if ($clinicalFieldDefinitions->isNotEmpty())
    <section class="card clinical-fields-card" aria-labelledby="clinical-fields-title">
        <div class="section-heading">
            <div>
                <span class="eyebrow">اطلاعات تکمیلی</span>
                <h2 id="clinical-fields-title">فیلدهای سفارشی پرونده</h2>
            </div>
            @if ($canEditClinical)
                <span class="status-badge status-badge--info">قابل ویرایش</span>
            @endif
        </div>

        @if ($canEditClinical)
            <form method="post" action="{{ route('patients.clinical-fields.store', ['patientId' => $patient->id]) }}" class="stack-form">
                @csrf
                <div class="form-grid">
                    @foreach ($clinicalFieldDefinitions as $definition)
                        @php($savedValue = data_get($clinicalFieldValues->get($definition->id)?->value, 'value'))
                        <div class="field {{ $definition->field_type === 'textarea' ? 'field--wide' : '' }}">
                            <label for="clinical-field-{{ $definition->id }}">{{ $definition->label }}@if ($definition->is_required) <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span>@endif</label>
                            @if ($definition->field_type === 'textarea')
                                <textarea id="clinical-field-{{ $definition->id }}" name="fields[{{ $definition->id }}]" rows="3">{{ old("fields.{$definition->id}", $savedValue) }}</textarea>
                            @elseif ($definition->field_type === 'select')
                                <select id="clinical-field-{{ $definition->id }}" name="fields[{{ $definition->id }}]">
                                    <option value="">انتخاب کنید</option>
                                    @foreach ($definition->options ?? [] as $option)
                                        <option value="{{ $option }}" @selected(old("fields.{$definition->id}", $savedValue) === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            @elseif ($definition->field_type === 'boolean')
                                <label class="checkbox-field" for="clinical-field-{{ $definition->id }}">
                                    <input id="clinical-field-{{ $definition->id }}" type="checkbox" name="fields[{{ $definition->id }}]" value="1" @checked((bool) old("fields.{$definition->id}", $savedValue))>
                                    <span>بله</span>
                                </label>
                            @else
                                <input id="clinical-field-{{ $definition->id }}" name="fields[{{ $definition->id }}]" type="{{ $definition->field_type }}" value="{{ old("fields.{$definition->id}", $savedValue) }}" @if ($definition->field_type === 'number') dir="ltr" inputmode="decimal" @endif>
                            @endif
                            @error("fields.{$definition->id}")<small class="field-error">{{ $message }}</small>@enderror
                        </div>
                    @endforeach
                </div>
                <div class="inline-actions"><button class="button button--primary" type="submit">ذخیرهٔ اطلاعات تکمیلی</button></div>
            </form>
        @else
            <div class="detail-list">
                @foreach ($clinicalFieldDefinitions as $definition)
                    @php($savedValue = data_get($clinicalFieldValues->get($definition->id)?->value, 'value'))
                    <div class="detail-row"><strong>{{ $definition->label }}</strong><span>{{ is_bool($savedValue) ? ($savedValue ? 'بله' : 'خیر') : ($savedValue ?: '—') }}</span></div>
                @endforeach
            </div>
        @endif
    </section>
@endif

@if ($canViewMedicalFiles || $canCreateMedicalFiles)
    <section class="card medical-files-card" aria-labelledby="medical-files-title">
        <div class="section-heading">
            <div>
                <span class="eyebrow">اسناد حساس پرونده</span>
                <h2 id="medical-files-title">فایل‌های پزشکی</h2>
            </div>
            <span class="status-badge status-badge--info">{{ $patient->fileAssets->count() }}</span>
        </div>

        @if ($canCreateMedicalFiles)
            <form method="post" action="{{ route('patients.medical-files.store', ['patientId' => $patient->id]) }}" enctype="multipart/form-data" class="medical-files-form">
                @csrf
                <div class="field">
                    <label for="medical-file">انتخاب فایل <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                    <input id="medical-file" name="file" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                    <small>فقط JPG، JPEG یا PNG تا سقف ۱ مگابایت.</small>
                    @error('file')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="medical-file-category">دستهٔ فایل <span aria-hidden="true">*</span><span class="sr-only"> الزامی</span></label>
                    <select id="medical-file-category" name="category" required>
                        <option value="">انتخاب کنید</option>
                        @foreach ($medicalFileCategories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="medical-file-title">عنوان اختیاری</label>
                    <input id="medical-file-title" name="title" type="text" value="{{ old('title') }}" maxlength="120" placeholder="مثلاً رادیوگرافی اولیه">
                    @error('title')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <button class="button button--primary" type="submit"><x-ui.icon name="check" size="17" /> ذخیرهٔ فایل</button>
            </form>
            <p class="medical-file-hint">فایل در فضای خصوصی ذخیره می‌شود و URL عمومی ندارد.</p>
        @endif

        @if ($canViewMedicalFiles)
            <div class="medical-file-list">
                @forelse ($patient->fileAssets as $asset)
                    <article class="medical-file-row">
                        <div class="medical-file-row__meta">
                            <span class="medical-file-row__icon" aria-hidden="true"><x-ui.icon name="invoice" size="18" /></span>
                            <div class="medical-file-row__text">
                                <strong>{{ $asset->title() }}</strong>
                                <small>{{ $medicalFileCategories[$asset->category] ?? 'فایل پزشکی' }} · {{ $asset->sizeInKilobytes() }} · {{ $asset->uploader?->name ?: 'کاربر سامانه' }} · <bdi dir="ltr">{{ $asset->created_at ? \App\Support\JalaliDate::format($asset->created_at) : '—' }}</bdi></small>
                            </div>
                        </div>
                        <div class="medical-file-row__actions">
                            <a class="button button--ghost button--small" href="{{ route('patients.medical-files.download', ['patientId' => $patient->id, 'fileId' => $asset->id]) }}"><x-ui.icon name="download" size="15" /> دریافت</a>
                            @if ($canArchiveMedicalFiles)
                                <form method="post" action="{{ route('patients.medical-files.archive', ['patientId' => $patient->id, 'fileId' => $asset->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button--danger button--small" type="submit">بایگانی</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="empty-state"><strong>فایل پزشکی ثبت نشده است.</strong><span>برای حفظ حریم خصوصی، فایل‌ها فقط داخل فضای خصوصی سامانه نگهداری می‌شوند.</span></div>
                @endforelse
            </div>
        @endif
    </section>
@endif

@if ($patient->treatmentPlans->isNotEmpty())
    <section class="card treatment-plan-summary" aria-labelledby="treatment-plans-title">
        <div class="section-heading"><div><span class="eyebrow">درمان فعال و سابقه</span><h2 id="treatment-plans-title">طرح‌های درمان</h2></div><span class="status-badge status-badge--info">{{ $patient->treatmentPlans->count() }}</span></div>
        @foreach ($patient->treatmentPlans->sortByDesc('id') as $plan)
            <details class="config-item" @if ($loop->first) open @endif>
                <summary><span><strong>{{ $plan->title }}</strong><small>وضعیت: {{ ['draft' => 'پیش‌نویس', 'active' => 'فعال', 'on_hold' => 'متوقف', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده'][$plan->status] ?? $plan->status }} · برآورد: <bdi dir="ltr">{{ number_format((float) ($plan->estimated_total ?? 0)) }}</bdi> ریال</small></span><span class="status-badge status-badge--info">{{ $plan->items->count() }} آیتم</span></summary>
                <div class="table-wrap config-item__form">
                    <table class="data-table treatment-items-table">
                        <thead><tr><th>مرحله</th><th>دندان</th><th>وضعیت</th><th>هزینه</th><th>عملیات</th></tr></thead>
                        <tbody>
                            @foreach ($plan->items->sortBy('sort_order') as $item)
                                <tr>
                                    <td>{{ $item->stage?->name ?: '—' }}</td>
                                    <td dir="ltr"><bdi>{{ $item->tooth_code ?: '—' }}{{ $item->surface_code ? ' / '.$item->surface_code : '' }}</bdi></td>
                                    <td>{{ ['planned' => 'برنامه‌ریزی‌شده', 'approved' => 'تأییدشده', 'in_progress' => 'در حال انجام', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده'][$item->status] ?? $item->status }}</td>
                                    <td dir="ltr"><bdi>{{ $item->estimated_cost !== null ? number_format((float) $item->estimated_cost) : '—' }}</bdi></td>
                                    <td>
                                        @if ($canUpdateTreatments)
                                            <form method="post" action="{{ route('treatment-plan-items.status.update', ['itemId' => $item->id]) }}" class="inline-status-form">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" aria-label="وضعیت جدید آیتم {{ $loop->iteration }}"><option value="planned">برنامه‌ریزی</option><option value="approved">تأیید</option><option value="in_progress">در حال انجام</option><option value="completed">تکمیل</option><option value="cancelled">لغو</option></select>
                                                <input name="reason" placeholder="دلیل در صورت لغو" aria-label="دلیل تغییر وضعیت">
                                                <button class="button button--small button--secondary" type="submit">ثبت</button>
                                            </form>
                                        @else
                                            <span class="muted">فقط مشاهده</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endforeach
    </section>
@endif

<section class="patient-detail-grid">
    <article class="card">
        <div class="section-heading section-heading--compact"><h2>حساسیت‌ها</h2><span class="status-badge status-badge--danger">{{ $patient->allergies->count() }}</span></div>
        @forelse ($patient->allergies as $allergy)
            <div class="detail-row"><strong>{{ $allergy->substance_name }}</strong><span>{{ $allergy->reaction ?: 'واکنش ثبت نشده' }}</span><span>{{ $allergy->is_critical ? 'بحرانی' : 'عادی' }}</span></div>
        @empty
            <p class="muted">حساسیتی ثبت نشده است.</p>
        @endforelse
    </article>
    <article class="card">
        <div class="section-heading section-heading--compact"><h2>داروهای فعال</h2><span class="status-badge status-badge--info">{{ $patient->medications->where('is_active', true)->count() }}</span></div>
        @forelse ($patient->medications->where('is_active', true) as $medication)
            <div class="detail-row"><strong>{{ $medication->name }}</strong><span>{{ $medication->dosage ?: '—' }}</span><span>{{ $medication->frequency ?: '—' }}</span></div>
        @empty
            <p class="muted">داروی فعالی ثبت نشده است.</p>
        @endforelse
    </article>
    <article class="card">
        <div class="section-heading section-heading--compact"><h2>سوابق پزشکی</h2><span class="status-badge status-badge--info">{{ $patient->conditions->count() }}</span></div>
        @forelse ($patient->conditions as $condition)
            <div class="detail-row"><strong>{{ $condition->condition?->name ?: '—' }}</strong><span>{{ $condition->value ?: '—' }}</span></div>
        @empty
            <p class="muted">سابقه‌ای ثبت نشده است.</p>
        @endforelse
    </article>
    <article class="card">
        <div class="section-heading section-heading--compact"><h2>یادداشت‌ها</h2><span class="status-badge status-badge--info">{{ $patient->notes->count() }}</span></div>
        @forelse ($patient->notes as $note)
            <div class="note-block"><strong>{{ $note->author?->name ?: 'کاربر سامانه' }}</strong><small>{{ $note->created_at ? \App\Support\JalaliDate::format($note->created_at).' · '.$note->created_at->format('H:i') : '—' }}</small><p>{{ $note->body }}</p></div>
        @empty
            <p class="muted">یادداشتی ثبت نشده است.</p>
        @endforelse
    </article>
</section>
@endsection
