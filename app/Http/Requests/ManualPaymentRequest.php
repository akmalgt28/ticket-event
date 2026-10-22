<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class ManualPaymentRequest extends FormRequest
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
            'method' => ['required', 'in:bank_transfer'],
            'customer_email' => ['required', 'email', 'max:255'],
        ];
    }
}
