<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OvkUsageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'header_id' => \App\Models\DailyActivityHeader::inRandomOrder()->first()?->id ?? \App\Models\DailyActivityHeader::factory(),
            'ovk_item_id' => \App\Models\OvkItem::inRandomOrder()->first()?->id ?? \App\Models\OvkItem::factory(),
            'quantity' => $this->faker->randomFloat(2, 1, 10),
            'notes' => $this->faker->sentence,
            'sync_status' => 'LOCAL_SAVED',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ];
    }
}
