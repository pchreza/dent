<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer'],
            'description' => ['required', 'string', 'max:240'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
