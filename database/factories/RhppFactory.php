<?php

namespace Database\Factories;

use App\Models\Rhpp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rhpp>
 */
class RhppFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'server_id' => $this->faker->unique()->randomNumber(),
            'version' => 1,
            'period_id' => \App\Models\ProductionPeriod::factory(),
            'total_income' => $this->faker->randomFloat(2, 5000000, 10000000),
            'total_expense' => $this->faker->randomFloat(2, 1000000, 5000000),
            'net_profit' => $this->faker->randomFloat(2, 1000000, 5000000),
            'publish_status' => $this->faker->randomElement(['DRAFT', 'PUBLISHED', 'ARCHIVED']),
            'sync_status' => $this->faker->randomElement(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT']),
            'created_at_client' => $this->faker->dateTime(),
            'created_at_server' => $this->faker->optional()->dateTime(),
            'updated_at_client' => $this->faker->dateTime(),
            'updated_at_server' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
        ];
    }
}
