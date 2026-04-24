<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\ProductionPeriod;
use App\Models\User;

class DailyActivityHeaderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'period_id' => \App\Models\ProductionPeriod::inRandomOrder()->first()?->id ?? \App\Models\ProductionPeriod::factory(),
            'user_id' => \App\Models\User::inRandomOrder()->first()?->id ?? \App\Models\User::factory(),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'age_days' => $this->faker->numberBetween(1, 35),
            'mortality_count' => $this->faker->numberBetween(0, 10),
            'cull_count' => $this->faker->numberBetween(0, 5),
            'average_weight' => $this->faker->randomFloat(2, 0.1, 2.5),
            'business_status' => 'DRAFT',
            'sync_status' => 'LOCAL_SAVED',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ];
    }
}
