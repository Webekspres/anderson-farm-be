<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PhotoEvidenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'header_id' => \App\Models\DailyActivityHeader::inRandomOrder()->first()?->id ?? \App\Models\DailyActivityHeader::factory(),
            'photo_path_local' => '/local/path/' . $this->faker->word . '.jpg',
            'photo_url' => $this->faker->imageUrl(),
            'photo_type' => $this->faker->randomElement(['mortality', 'feed_empty', 'general']),
            'description' => $this->faker->sentence,
            'sync_status' => 'LOCAL_SAVED',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ];
    }
}
