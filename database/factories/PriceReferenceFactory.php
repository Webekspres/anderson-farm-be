<?php

namespace Database\Factories;

use App\Models\PriceReference;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PriceReferenceFactory extends Factory
{
    protected $model = PriceReference::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'server_id' => $this->faker->unique()->randomNumber(),
            'version' => 1,
            'name' => $this->faker->sentence(3),
            'highlight_price' => $this->faker->optional()->randomFloat(2, 1000, 100000),
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
