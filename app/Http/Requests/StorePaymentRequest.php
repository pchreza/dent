<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'method' => ['required', 'in:cash,card,transfer,online,insurance,other'],
            'reference' => ['nullable', 'string', 'max:160'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
