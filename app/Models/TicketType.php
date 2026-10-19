<?php

namespace App\Models;

use App\Enums\TicketTypeStatus;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'event_id',
    'name',
    'description',
    'price',
    'quota',
    'sold_count',
    'sale_start_at',
    'sale_end_at',
    'status',
])]
class TicketType extends Model
{
    /** @use HasFactory<TicketTypeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TicketTypeStatus::class,
            'price' => 'decimal:2',
            'quota' => 'integer',
            'sold_count' => 'integer',
            'sale_start_at' => 'datetime',
            'sale_end_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeAvailableForSale(Builder $query): void
    {
        $now = now();

        $query
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('sale_start_at')
                    ->orWhere('sale_start_at', '<=', $now);
            })
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('sale_end_at')
                    ->orWhere('sale_end_at', '>=', $now);
            });
    }
}
