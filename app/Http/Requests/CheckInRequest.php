<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('checkin.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'token' => ['nullable', 'string', 'max:255', 'required_without:ticket_number'],
            'ticket_number' => ['nullable', 'string', 'max:255', 'required_without:token'],
        ];
    }
}
