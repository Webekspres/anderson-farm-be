<?php

namespace Database\Factories;

use App\Models\DailyActivityHeader;
use App\Models\FormConfig;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DailyDynamicLogFactory extends Factory
{
    public function definition(): array
    {
        $valueNumeric = $this->faker->optional(0.8)->randomFloat(2, 20, 35);
        $valueBoolean = $this->faker->optional(0.5)->boolean();

        return [
            'id' => Str::uuid()->toString(),
            'header_id' => DailyActivityHeader::factory(),
            'form_config_id' => FormConfig::factory(),
            'value' => $valueNumeric !== null ? (string) $valueNumeric : ($valueBoolean !== null ? ($valueBoolean ? 'true' : 'false') : $this->faker->word()),
            'value_numeric' => $valueNumeric,
            'value_boolean' => $valueBoolean,
            'sync_status' => 'LOCAL_SAVED',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ];
    }
}
