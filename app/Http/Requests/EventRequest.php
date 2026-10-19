<?php

namespace App\Http\Requests;

use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('events.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'location_name' => ['required', 'string', 'max:255'],
            'location_address' => ['required', 'string'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'status' => ['sometimes', Rule::enum(EventStatus::class)],
        ];
    }
}
