<fieldset class="treatment-item" data-treatment-item>
    <legend>آیتم <span data-treatment-item-number>{{ is_numeric($index) ? ((int) $index + 1) : '' }}</span></legend>
    <button class="button button--ghost button--small treatment-item__remove" type="button" data-treatment-item-remove>حذف آیتم</button>
    <div class="form-grid">
        <div class="field">
            <label for="stage-{{ $index }}">مرحلهٔ درمان</label>
            <select id="stage-{{ $index }}" name="items[{{ $index }}][stage_id]" required>
                <option value="">انتخاب مرحله</option>
                @foreach ($stages as $stage)<option value="{{ $stage->id }}" @selected((string) ($item['stage_id'] ?? '') === (string) $stage->id)>{{ $stage->name }}</option>@endforeach
            </select>
        </div>
        <div class="field">
            <label for="treatment-{{ $index }}">خدمت کاتالوگ</label>
            <select id="treatment-{{ $index }}" name="items[{{ $index }}][treatment_id]">
                <option value="">بدون اتصال به خدمت</option>
                @foreach ($treatments as $treatment)<option value="{{ $treatment->id }}" @selected((string) ($item['treatment_id'] ?? '') === (string) $treatment->id)>{{ $treatment->name }}@if ($treatment->default_price) — {{ number_format((float) $treatment->default_price) }} ریال@endif</option>@endforeach
            </select>
        </div>
        <div class="field">
            <label for="tooth-{{ $index }}">دندان</label>
            <select id="tooth-{{ $index }}" name="items[{{ $index }}][tooth_code]" dir="ltr">
                <option value="">بدون اتصال</option>
                <optgroup label="دائمی">@foreach (\App\Models\DentalChartEntry::PERMANENT_TEETH as $code)<option value="{{ $code }}" @selected(($item['tooth_code'] ?? '') === $code)>{{ $code }}</option>@endforeach</optgroup>
                <optgroup label="شیری">@foreach (\App\Models\DentalChartEntry::PRIMARY_TEETH as $code)<option value="{{ $code }}" @selected(($item['tooth_code'] ?? '') === $code)>{{ $code }}</option>@endforeach</optgroup>
            </select>
        </div>
        <div class="field">
            <label for="surface-{{ $index }}">سطح</label>
            <select id="surface-{{ $index }}" name="items[{{ $index }}][surface_code]">
                <option value="">کل دندان/ندارد</option>
                @foreach (\App\Models\DentalChartEntry::SURFACES as $surface)<option value="{{ $surface }}" @selected(($item['surface_code'] ?? '') === $surface) dir="ltr">{{ $surface === 'all' ? 'کل دندان' : $surface }}</option>@endforeach
            </select>
        </div>
        <div class="field">
            <label for="priority-{{ $index }}">اولویت</label>
            <select id="priority-{{ $index }}" name="items[{{ $index }}][priority]">
                @foreach (['low' => 'کم', 'normal' => 'عادی', 'high' => 'زیاد', 'urgent' => 'فوری'] as $priority => $label)<option value="{{ $priority }}" @selected(($item['priority'] ?? 'normal') === $priority)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="field">
            <label for="item-status-{{ $index }}">وضعیت اولیه</label>
            <select id="item-status-{{ $index }}" name="items[{{ $index }}][status]">
                @foreach (['planned' => 'برنامه‌ریزی‌شده', 'approved' => 'تأییدشده', 'in_progress' => 'در حال انجام', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده'] as $status => $label)<option value="{{ $status }}" @selected(($item['status'] ?? 'planned') === $status)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="field"><label for="cost-{{ $index }}">هزینهٔ برآوردی (ریال)</label><input id="cost-{{ $index }}" name="items[{{ $index }}][estimated_cost]" type="number" min="0" step="0.01" value="{{ $item['estimated_cost'] ?? '' }}" dir="ltr" inputmode="decimal"></div>
        <div class="field"><label for="planned-on-{{ $index }}">تاریخ برنامه‌ریزی</label><input id="planned-on-{{ $index }}" name="items[{{ $index }}][planned_on]" type="date" value="{{ $item['planned_on'] ?? '' }}" dir="ltr"></div>
        <div class="field field--wide"><label for="item-notes-{{ $index }}">یادداشت آیتم</label><textarea id="item-notes-{{ $index }}" name="items[{{ $index }}][notes]" rows="2">{{ $item['notes'] ?? '' }}</textarea></div>
    </div>
</fieldset>
