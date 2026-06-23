<?php

namespace Database\Factories;

use App\Models\CoopDocument;
use App\Models\CoopFloor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoopDocument>
 */
class CoopDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 2147483646),
            'version' => 1,
            'floor_id' => CoopFloor::factory(),
            'name' => $this->faker->sentence(3),
            'file_path_local' => $this->faker->optional()->lexify('docs/??????.pdf'),
            'file_url' => $this->faker->optional()->url(),
            'file_type' => $this->faker->randomElement(['pdf', 'jpg', 'png']),
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => $this->faker->dateTime(),
            'created_at_server' => $this->faker->optional()->dateTime(),
            'updated_at_client' => $this->faker->dateTime(),
            'updated_at_server' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
        ];
    }
}
