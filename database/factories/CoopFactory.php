<?php

namespace Database\Factories;

use App\Models\Coop;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoopFactory extends Factory
{
    protected $model = Coop::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999),
            'version' => 1,
            'farm_id' => Farm::factory(),
            'name' => 'Kdg ' . $this->faker->city(),
            'capacity' => $this->faker->numberBetween(1000, 50000),
            'floor' => $this->faker->numberBetween(1, 3),
            'coop_type' => $this->faker->randomElement(['open_house', 'closed_house']),
            'note' => $this->faker->optional()->sentence(),
            'is_active' => $this->faker->boolean(90),
            'sync_status' => $this->faker->randomElement(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT']),
            'created_at_client' => $this->faker->dateTime(),
            'created_at_server' => $this->faker->optional()->dateTime(),
            'updated_at_client' => $this->faker->dateTime(),
            'updated_at_server' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
            'sync_metadata' => null,
        ];
    }
}
