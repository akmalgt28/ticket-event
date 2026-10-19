<?php

namespace Tests\Feature;

use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\EventRequest;
use App\Http\Requests\TicketTypeRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    public function test_event_request_rejects_end_before_start(): void
    {
        $request = EventRequest::create('/', 'POST', [
            'start_at' => '2026-10-20 10:00:00',
            'end_at' => '2026-10-20 09:00:00',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('end_at', $validator->errors()->toArray());
    }

    public function test_checkout_request_rejects_client_calculated_totals(): void
    {
        $request = CheckoutRequest::create('/', 'POST', [
            'total_amount' => 100000,
            'subtotal' => 100000,
            'discount_amount' => 0,
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('total_amount', $validator->errors()->toArray());
        $this->assertArrayHasKey('subtotal', $validator->errors()->toArray());
        $this->assertArrayHasKey('discount_amount', $validator->errors()->toArray());
    }

    public function test_ticket_type_request_rejects_client_controlled_sold_count(): void
    {
        $request = TicketTypeRequest::create('/', 'POST', [
            'sold_count' => 10,
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sold_count', $validator->errors()->toArray());
    }
}
