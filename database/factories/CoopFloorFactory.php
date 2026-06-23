<?php

namespace Database\Factories;

use App\Models\Coop;
use App\Models\CoopFloor;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoopFloorFactory extends Factory
{
    protected $model = CoopFloor::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999999),
            'coop_id' => Coop::factory(),
            'name' => 'Lantai '.$this->faker->numberBetween(1, 3),
            'capacity' => $this->faker->numberBetween(1000, 50000),
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
