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
            'token' => ['required', 'string', 'max:255'],
        ];
    }
}
