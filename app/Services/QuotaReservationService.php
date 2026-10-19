<?php

namespace App\Services;

use App\Enums\TicketTypeStatus;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotaReservationService
{
    /**
     * @param  array<int, array{ticket_type_id: int, quantity: int}>  $items
     */
    public function reserve(Event $event, array $items): void
    {
        $quantities = [];

        foreach ($items as $item) {
            $ticketTypeId = (int) $item['ticket_type_id'];
            $quantities[$ticketTypeId] = ($quantities[$ticketTypeId] ?? 0) + (int) $item['quantity'];
        }

        DB::transaction(function () use ($event, $quantities): void {
            $ticketTypes = $event->ticketTypes()
                ->whereKey(array_keys($quantities))
                ->where('status', TicketTypeStatus::Active)
                ->availableForSale()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($ticketTypes->count() !== count($quantities)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more ticket types are not available for sale.',
                ]);
            }

            foreach ($quantities as $ticketTypeId => $quantity) {
                $ticketType = $ticketTypes->get($ticketTypeId);

                if ($ticketType->sold_count + $quantity > $ticketType->quota) {
                    throw ValidationException::withMessages([
                        'items' => "Ticket type {$ticketType->name} does not have enough quota.",
                    ]);
                }

                $ticketType->increment('sold_count', $quantity);
            }
        });
    }
}
