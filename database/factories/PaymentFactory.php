<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'manual',
            'method' => 'bank_transfer',
            'provider_transaction_id' => null,
            'amount' => 150000,
            'status' => 'pending',
            'paid_at' => null,
            'raw_response' => null,
        ];
    }
}
