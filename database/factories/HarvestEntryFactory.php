<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HarvestEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'header_id' => \App\Models\DailyActivityHeader::inRandomOrder()->first()?->id ?? \App\Models\DailyActivityHeader::factory(),
            'rit_number' => $this->faker->numberBetween(1, 5),
            'buyer_name' => $this->faker->company,
            'total_birds' => $this->faker->numberBetween(100, 500),
            'total_weight' => $this->faker->randomFloat(2, 100, 1000),
            'price_per_kg' => 20000,
            'total_revenue' => $this->faker->randomFloat(2, 1000000, 20000000),
            'delivery_order_no' => $this->faker->bothify('DO-####-????'),
            'sync_status' => 'LOCAL_SAVED',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ];
    }
}
