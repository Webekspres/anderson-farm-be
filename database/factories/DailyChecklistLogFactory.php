<?php

namespace Database\Factories;

use App\Models\ChecklistTask;
use App\Models\DailyActivityHeader;
use App\Models\ProductionPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DailyChecklistLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999999),
            'header_id' => DailyActivityHeader::factory(),
            'period_id' => null,
            'task_id' => ChecklistTask::factory(),
            'boolean_value' => $this->faker->boolean,
            'text_value' => $this->faker->word,
            'notes' => $this->faker->sentence,
            'sync_status' => 'LOCAL_SAVED',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ];
    }

    public function preChickIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'header_id' => null,
            'period_id' => ProductionPeriod::factory(),
        ]);
    }
}
