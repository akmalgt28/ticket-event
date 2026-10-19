<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketType;
use App\Services\OrderCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_totals_are_calculated_from_ticket_type_prices(): void
    {
        $event = Event::factory()->create();
        $regular = TicketType::factory()->for($event)->create(['price' => '150.00']);
        $vip = TicketType::factory()->for($event)->create(['price' => '75.50']);

        $result = app(OrderCalculator::class)->calculate($event, [
            ['ticket_type_id' => $regular->id, 'quantity' => 2],
            ['ticket_type_id' => $vip->id, 'quantity' => 1],
        ]);

        $this->assertSame('375.50', $result['subtotal']);
        $this->assertSame('375.50', $result['total_amount']);
        $this->assertCount(2, $result['items']);
    }

    public function test_order_calculator_rejects_ticket_type_from_another_event(): void
    {
        $event = Event::factory()->create();
        $otherEventTicket = TicketType::factory()->create();

        $this->expectException(ValidationException::class);
        app(OrderCalculator::class)->calculate($event, [
            ['ticket_type_id' => $otherEventTicket->id, 'quantity' => 1],
        ]);
    }
}
