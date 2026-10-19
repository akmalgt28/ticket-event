<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'in:bank_transfer'],
        ];
    }
}
