<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class ManualPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order
            && hash_equals(strtolower($order->customer_email), strtolower((string) $this->input('customer_email')));
    }

    public function rules(): array
    {
        return [
            'customer_email' => ['required', 'email', 'max:255'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
