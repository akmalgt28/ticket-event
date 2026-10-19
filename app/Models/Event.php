<?php

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'slug',
    'description',
    'banner_path',
    'start_at',
    'end_at',
    'location_name',
    'location_address',
    'contact_email',
    'contact_phone',
    'status',
    'published_at',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
