<?php

namespace Database\Factories;

use App\Models\ProductionPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionPeriod>
 */
class ProductionPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999),
            'version' => 1,
            'floor_id' => fn() => \App\Models\CoopFloor::factory(),
            'pic_id' => fn() => \App\Models\User::factory(),
            'period_code' => $this->faker->unique()->bothify('PERIOD-####'),
            'start_date' => $this->faker->dateTimeBetween('-4 months', 'now'),
            'end_date' => $this->faker->optional()->dateTimeBetween('-4 months', 'now'),
            'initial_stock' => $this->faker->numberBetween(100, 1000),
            'closing_reason' => $this->faker->optional()->sentence(),
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => now(),
            'created_at_server' => now(),
            'updated_at_client' => now(),
            'updated_at_server' => now(),
            'deleted_at' => null,
        ];
    }
}
