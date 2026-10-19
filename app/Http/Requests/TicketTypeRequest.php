<?php

namespace App\Http\Requests;

use App\Enums\TicketTypeStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $event = $this->route('event');

        if ($event instanceof Event) {
            $this->merge(['event_id' => $event->id]);
        }
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'quota' => ['required', 'integer', 'min:0'],
            'sale_start_at' => ['nullable', 'date'],
            'sale_end_at' => ['nullable', 'date', 'after_or_equal:sale_start_at'],
            'status' => ['sometimes', Rule::enum(TicketTypeStatus::class)],
            'sold_count' => ['prohibited'],
        ];
    }
}
