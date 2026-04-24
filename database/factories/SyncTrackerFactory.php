<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SyncTrackerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'table_name' => $this->faker->unique()->word . '_table',
            'last_server_id' => $this->faker->numberBetween(1, 10000),
            'last_sync_at' => now(),
        ];
    }
}
