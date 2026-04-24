<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DailyDynamicLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'header_id' => \App\Models\DailyActivityHeader::inRandomOrder()->first()?->id ?? \App\Models\DailyActivityHeader::factory(),
            'form_config_id' => \App\Models\FormConfig::inRandomOrder()->first()?->id ?? \App\Models\FormConfig::factory(),
            'value' => $this->faker->word,
            'sync_status' => 'LOCAL_SAVED',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ];
    }
}
