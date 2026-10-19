<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketIssuanceService
{
    /**
     * @return array{tickets: Collection<int, Ticket>, qr_tokens: array<int, string>}
     */
    public function issue(Order $order): array
    {
        return DB::transaction(function () use ($order): array {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->order_status !== OrderStatus::Paid) {
                throw ValidationException::withMessages([
                    'order' => 'Tickets can only be issued for paid orders.',
                ]);
            }

            $existingTickets = $lockedOrder->tickets()->get();

            if ($existingTickets->isNotEmpty()) {
                return ['tickets' => $existingTickets, 'qr_tokens' => []];
            }

            $lockedOrder->load(['items', 'attendees']);
            $tickets = collect();
            $qrTokens = [];
            $attendeeIndex = 0;

            foreach ($lockedOrder->items as $orderItem) {
                for ($index = 0; $index < $orderItem->quantity; $index++) {
                    $token = Str::random(64);
                    $attendee = $lockedOrder->attendees[$attendeeIndex] ?? null;
                    $ticket = Ticket::create([
                        'order_id' => $lockedOrder->id,
                        'order_item_id' => $orderItem->id,
                        'attendee_id' => $attendee?->id,
                        'ticket_number' => 'TKT-'.strtoupper(str_replace('-', '', (string) Str::uuid())),
                        'qr_token_hash' => hash('sha256', $token),
                        'qr_token' => $token,
                        'status' => TicketStatus::Issued,
                    ]);

                    $tickets->push($ticket);
                    $qrTokens[$ticket->id] = $token;
                    $attendeeIndex++;
                }
            }

            return ['tickets' => $tickets, 'qr_tokens' => $qrTokens];
        });
    }
}
