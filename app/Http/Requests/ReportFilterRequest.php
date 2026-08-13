<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\JalaliDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $report = (string) $this->route('report', '');
        $statuses = match ($report) {
            'appointments' => ['scheduled', 'confirmed', 'arrived', 'in_treatment', 'completed', 'cancelled', 'no_show'],
            'treatments' => ['draft', 'proposed', 'approved', 'in_progress', 'completed', 'cancelled', 'rejected'],
            'finance' => ['issued', 'open', 'partially_paid', 'paid', 'overdue', 'cancelled', 'waived', 'refunded'],
            default => [],
        };

        return [
            'from' => ['nullable', 'string', 'regex:/^\\d{4}\\/\\d{2}\\/\\d{2}$/'],
            'to' => ['nullable', 'string', 'regex:/^\\d{4}\\/\\d{2}\\/\\d{2}$/'],
            'status' => ['nullable', 'string', 'max:40', Rule::in($statuses)],
            'branch_id' => ['nullable', 'integer', 'min:1'],
            'practitioner_id' => ['nullable', 'integer', 'min:1'],
            'treatment_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:120'],
            'method' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = $this->input('from');
            $to = $this->input('to');
            $parsed = [];

            foreach (['from', 'to'] as $field) {
                $value = $this->input($field);

                if ($value === null || $value === '' || $validator->errors()->has($field)) {
                    continue;
                }

                try {
                    $parsed[$field] = JalaliDate::parse((string) $value);
                } catch (\Throwable) {
                    $validator->errors()->add($field, $field === 'from' ? 'تاریخ شروع شمسی معتبر نیست.' : 'تاریخ پایان شمسی معتبر نیست.');
                }
            }

            if ($from !== null && $to !== null && isset($parsed['from'], $parsed['to']) && $parsed['from']->greaterThan($parsed['to'])) {
                $validator->errors()->add('to', 'تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'from.regex' => 'تاریخ شروع باید با فرمت ۱۴۰۵/۰۱/۰۱ وارد شود.',
            'to.regex' => 'تاریخ پایان باید با فرمت ۱۴۰۵/۰۱/۰۱ وارد شود.',
            'status.in' => 'وضعیت انتخاب‌شده برای این گزارش معتبر نیست.',
            'branch_id.integer' => 'شناسهٔ شعبه معتبر نیست.',
            'practitioner_id.integer' => 'شناسهٔ پزشک معتبر نیست.',
            'treatment_id.integer' => 'شناسهٔ خدمت معتبر نیست.',
            'search.max' => 'عبارت جست‌وجو نباید بیشتر از ۱۲۰ نویسه باشد.',
            'method.max' => 'روش پرداخت واردشده معتبر نیست.',
        ];
    }
}
