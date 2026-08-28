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
            'content_html' => '<p>'.$this->faker->paragraph(3).'</p>',
            'category' => $this->faker->optional()->randomElement(['Penyakit', 'Nutrisi', 'Manajemen']),
            'author_name' => $this->faker->optional()->name(),
            'link_url' => $this->faker->optional()->url(),
            'image_url' => $this->faker->optional()->imageUrl(),
            'image_path_local' => $this->faker->optional()->filePath(),
            'sync_status' => $this->faker->randomElement(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT']),
            'created_at_client' => $this->faker->dateTime(),
            'created_at_server' => $this->faker->optional()->dateTime(),
            'updated_at_client' => $this->faker->dateTime(),
            'updated_at_server' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
        ];
    }
}
