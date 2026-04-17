<?php

namespace Database\Factories;

use App\Models\EducationArticle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EducationArticleFactory extends Factory
{
    protected $model = EducationArticle::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'server_id' => $this->faker->unique()->randomNumber(),
            'version' => 1,
            'name' => $this->faker->sentence(3),
            'excerpt' => $this->faker->optional()->sentence(8),
            'link_url' => $this->faker->optional()->url(),
            'image_url' => $this->faker->optional()->imageUrl(),
            'image_path_local' => $this->faker->optional()->filePath(),
            'created_at' => $this->faker->optional()->dateTime(),
            'updated_at' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
        ];
    }
}
