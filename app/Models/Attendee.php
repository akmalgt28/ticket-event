<?php

namespace App\Models;

use Database\Factories\AttendeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['order_id', 'name', 'email', 'phone', 'metadata'])]
class Attendee extends Model
{
    /** @use HasFactory<AttendeeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
