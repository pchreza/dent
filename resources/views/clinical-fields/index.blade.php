@extends('layouts.app', ['title' => 'تنظیم فیلدهای پرونده'])

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">{{ $tenant->name }} · پروندهٔ پیشرفته</span>
        <h1>تنظیم فیلدهای سفارشی پرونده</h1>
        <p class="muted">فیلدهای غیرفعال از فرم پرونده حذف می‌شوند، اما اطلاعات ثبت‌شدهٔ پیشین آن‌ها محفوظ می‌ماند.</p>
    </div>
    <a class="button button--ghost" href="{{ route('patients.index') }}">بازگشت به بیماران</a>
</div>

<section class="two-column-layout">
    <article class="card">
        <div class="section-heading"><div><span class="eyebrow">تعریف جدید</span><h2>افزودن فیلد</h2></div></div>
        <form method="post" action="{{ route('clinical-fields.store') }}" class="stack-form">
            @csrf
            <div class="field">
                <label for="field-key">کلید فنی</label>
                <input id="field-key" name="key" value="{{ old('key') }}" dir="ltr" placeholder="smoking_status" required>
                <small>پس از ایجاد تغییر نمی‌کند؛ فقط حروف انگلیسی، عدد، خط تیره و زیرخط.</small>
            </div>
            <div class="field"><label for="field-label">عنوان نمایشی</label><input id="field-label" name="label" value="{{ old('label') }}" placeholder="وضعیت مصرف دخانیات" required></div>
            <div class="field">
                <label for="field-type">نوع فیلد</label>
                <select id="field-type" name="field_type" required>
                    <option value="text" @selected(old('field_type') === 'text')>متن کوتاه</option>
                    <option value="textarea" @selected(old('field_type') === 'textarea')>متن بلند</option>
                    <option value="number" @selected(old('field_type') === 'number')>عدد</option>
                    <option value="date" @selected(old('field_type') === 'date')>تاریخ</option>
                    <option value="boolean" @selected(old('field_type') === 'boolean')>بله/خیر</option>
                    <option value="select" @selected(old('field_type') === 'select')>فهرست انتخابی</option>
                </select>
            </div>
            <div class="field"><label for="field-options">گزینه‌های فهرست</label><textarea id="field-options" name="options_text" rows="3" placeholder="هر گزینه در یک خط">{{ old('options_text') }}</textarea><small>فقط برای نوع «فهرست انتخابی»؛ هر گزینه را در یک خط بنویسید.</small></div>
            <div class="form-grid">
                <div class="field"><label for="field-sort">ترتیب نمایش</label><input id="field-sort" name="sort_order" type="number" min="0" value="{{ old('sort_order', 0) }}" dir="ltr"></div>
                <label class="checkbox-field"><input type="checkbox" name="is_required" value="1" @checked(old('is_required'))><span>واردکردن این فیلد الزامی است</span></label>
            </div>
            <button class="button button--primary" type="submit">افزودن فیلد</button>
        </form>
    </article>

    <article class="card">
        <div class="section-heading"><div><span class="eyebrow">پیکربندی فعلی</span><h2>فیلدهای ثبت‌شده</h2></div><span class="status-badge status-badge--info">{{ $definitions->count() }}</span></div>
        @forelse ($definitions as $definition)
            <details class="config-item" @if ($loop->first) open @endif>
                <summary><span><strong>{{ $definition->label }}</strong><small dir="ltr"><bdi>{{ $definition->key }}</bdi></small></span><span class="status-badge {{ $definition->is_active ? 'status-badge--success' : 'status-badge--neutral' }}">{{ $definition->is_active ? 'فعال' : 'غیرفعال' }}</span></summary>
                <form method="post" action="{{ route('clinical-fields.update', ['definitionId' => $definition->id]) }}" class="stack-form config-item__form">
                    @csrf
                    @method('PATCH')
                    <div class="field"><label for="label-{{ $definition->id }}">عنوان نمایشی</label><input id="label-{{ $definition->id }}" name="label" value="{{ $definition->label }}" required></div>
                    <div class="field">
                        <label for="type-{{ $definition->id }}">نوع فیلد</label>
                        <select id="type-{{ $definition->id }}" name="field_type">
                            @foreach (\App\Models\ClinicalFieldDefinition::TYPES as $type)
                                <option value="{{ $type }}" @selected($definition->field_type === $type)>{{ ['text' => 'متن کوتاه', 'textarea' => 'متن بلند', 'number' => 'عدد', 'date' => 'تاریخ', 'boolean' => 'بله/خیر', 'select' => 'فهرست انتخابی'][$type] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label for="options-{{ $definition->id }}">گزینه‌های فهرست</label><textarea id="options-{{ $definition->id }}" name="options_text" rows="3">{{ implode("\n", $definition->options ?? []) }}</textarea></div>
                    <div class="form-grid">
                        <div class="field"><label for="sort-{{ $definition->id }}">ترتیب نمایش</label><input id="sort-{{ $definition->id }}" name="sort_order" type="number" min="0" value="{{ $definition->sort_order }}" dir="ltr"></div>
                        <label class="checkbox-field"><input type="checkbox" name="is_required" value="1" @checked($definition->is_required)><span>الزامی</span></label>
                        <label class="checkbox-field"><input type="checkbox" name="is_active" value="1" @checked($definition->is_active)><span>فعال</span></label>
                    </div>
                    <button class="button button--secondary" type="submit">ذخیرهٔ تنظیمات</button>
                </form>
            </details>
        @empty
            <p class="empty-state">هنوز فیلد سفارشی تعریف نشده است.</p>
        @endforelse
    </article>
</section>
@endsection
