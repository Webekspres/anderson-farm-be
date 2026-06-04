<?php

namespace Database\Factories;

use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DailyActivityHeaderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'period_id' => ProductionPeriod::inRandomOrder()->first()?->id ?? ProductionPeriod::factory(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
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

    public function submitted(): static
    {
        return $this->state(fn () => [
            'business_status' => 'SUBMITTED',
            'sync_status' => 'SYNCED',
            'updated_at_server' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'business_status' => 'REJECTED',
            'rejection_reason' => 'Data kematian tidak sesuai bukti foto.',
            'sync_status' => 'SYNCED',
            'updated_at_server' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'business_status' => 'APPROVED',
            'sync_status' => 'SYNCED',
            'updated_at_server' => now(),
        ]);
    }
}
