<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\AuthorizationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

final class StoreMedicalFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && app(AuthorizationService::class)->allows($this->user(), 'clinical_files.create');
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::image()->types(['jpg', 'jpeg', 'png'])->max('1024kb'),
            ],
            'category' => ['required', 'string', Rule::in(['xray', 'intraoral_photo', 'other'])],
            'title' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'انتخاب فایل پزشکی الزامی است.',
            'file.image' => 'فایل باید یک تصویر معتبر باشد.',
            'file.max' => 'حجم فایل نباید بیشتر از ۱ مگابایت باشد.',
            'file.types' => 'فقط فایل JPG، JPEG یا PNG پذیرفته می‌شود.',
            'category.required' => 'دستهٔ فایل را انتخاب کنید.',
            'category.in' => 'دستهٔ فایل انتخاب‌شده معتبر نیست.',
            'title.max' => 'عنوان فایل نباید بیشتر از ۱۲۰ نویسه باشد.',
        ];
    }
}
