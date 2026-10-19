<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'action' => 'created',
            'old_values' => null,
            'new_values' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Factory',
        ];
    }
}
