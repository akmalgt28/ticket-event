<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Validation\ValidationException;

class OrderCalculator
{
    /**
     * @param  array<int, array{ticket_type_id: int, quantity: int}>  $items
     * @return array{subtotal: string, discount_amount: string, total_amount: string, items: array<int, array{ticket_type_id: int, ticket_name_snapshot: string, unit_price: string, quantity: int, subtotal: string}>}
     */
    public function calculate(Event $event, array $items): array
    {
        $quantities = [];

        foreach ($items as $item) {
            $ticketTypeId = (int) $item['ticket_type_id'];
            $quantities[$ticketTypeId] = ($quantities[$ticketTypeId] ?? 0) + (int) $item['quantity'];
        }

        $ticketTypes = $event->ticketTypes()
            ->whereKey(array_keys($quantities))
            ->get()
            ->keyBy('id');

        if ($ticketTypes->count() !== count($quantities)) {
            throw ValidationException::withMessages([
                'items' => 'One or more ticket types do not belong to this event.',
            ]);
        }

        $subtotal = '0.00';
        $lineItems = [];

        foreach ($quantities as $ticketTypeId => $quantity) {
            $ticketType = $ticketTypes->get($ticketTypeId);
            $unitPrice = $ticketType->getRawOriginal('price');
            $lineSubtotal = bcmul($unitPrice, (string) $quantity, 2);
            $subtotal = bcadd($subtotal, $lineSubtotal, 2);

            $lineItems[] = [
                'ticket_type_id' => $ticketType->id,
                'ticket_name_snapshot' => $ticketType->name,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $lineSubtotal,
            ];
        }

        return [
            'subtotal' => $subtotal,
            'discount_amount' => '0.00',
            'total_amount' => $subtotal,
            'items' => $lineItems,
        ];
    }
}
