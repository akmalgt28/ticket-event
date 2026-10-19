<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.verify') ?? false;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'status' => ['required', Rule::in([PaymentStatus::Paid->value, PaymentStatus::Failed->value])],
            'provider_transaction_id' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
