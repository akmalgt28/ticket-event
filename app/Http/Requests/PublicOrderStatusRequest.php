<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string', 'max:50'],
            'customer_email' => ['required', 'email', 'max:255'],
        ];
    }
}
