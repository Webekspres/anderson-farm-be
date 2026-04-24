<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DailyChecklistLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'header_id' => \App\Models\DailyActivityHeader::inRandomOrder()->first()?->id ?? \App\Models\DailyActivityHeader::factory(),
            'task_id' => \App\Models\ChecklistTask::inRandomOrder()->first()?->id ?? \App\Models\ChecklistTask::factory(),
            'boolean_value' => $this->faker->boolean,
            'text_value' => $this->faker->word,
            'notes' => $this->faker->sentence,
            'sync_status' => 'LOCAL_SAVED',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ];
    }
}
