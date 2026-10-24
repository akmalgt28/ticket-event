<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('customer_email'))) {
            $this->merge(['customer_email' => strtolower(trim($this->input('customer_email')))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_type_id' => ['required', 'integer', 'distinct', 'exists:ticket_types,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'attendees' => ['required', 'array', 'min:1'],
            'attendees.*.name' => ['required', 'string', 'max:255'],
            'attendees.*.email' => ['required', 'email', 'max:255'],
            'attendees.*.phone' => ['required', 'string', 'max:30'],
            'attendees.*.metadata' => ['nullable', 'array'],
            'attendees.*.metadata.profesi' => ['nullable', 'string', 'max:255'],
            'attendees.*.metadata.brand' => ['nullable', 'string', 'max:255'],
            'attendees.*.metadata.domisili' => ['nullable', 'string', 'max:255'],
            'total_amount' => ['prohibited'],
            'subtotal' => ['prohibited'],
            'discount_amount' => ['prohibited'],
        ];
    }
}
