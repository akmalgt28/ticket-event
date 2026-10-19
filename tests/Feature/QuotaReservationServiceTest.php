<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketType;
use App\Services\QuotaReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuotaReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_quota_reservation_increments_sold_count(): void
    {
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create([
            'quota' => 3,
            'sold_count' => 1,
        ]);

        app(QuotaReservationService::class)->reserve($event, [
            ['ticket_type_id' => $ticketType->id, 'quantity' => 2],
        ]);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $ticketType->id,
            'sold_count' => 3,
        ]);
    }

    public function test_insufficient_quota_rolls_back_all_reservations(): void
    {
        $event = Event::factory()->create();
        $availableTicket = TicketType::factory()->for($event)->create(['quota' => 10]);
        $limitedTicket = TicketType::factory()->for($event)->create(['quota' => 1]);

        $this->expectException(ValidationException::class);
        try {
            app(QuotaReservationService::class)->reserve($event, [
                ['ticket_type_id' => $availableTicket->id, 'quantity' => 1],
                ['ticket_type_id' => $limitedTicket->id, 'quantity' => 2],
            ]);
        } finally {
            $this->assertDatabaseHas('ticket_types', [
                'id' => $availableTicket->id,
                'sold_count' => 0,
            ]);
        }
    }
}
