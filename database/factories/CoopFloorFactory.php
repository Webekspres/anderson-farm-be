<?php

namespace Database\Factories;

use App\Models\CoopFloor;
use App\Models\Coop;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoopFloorFactory extends Factory
{
    protected $model = CoopFloor::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'coop_id' => Coop::factory(),
            'name' => 'Lantai ' . $this->faker->numberBetween(1, 3),
            'capacity' => $this->faker->numberBetween(1000, 50000),
            'coop_type' => $this->faker->randomElement(['CH_POSTAL', 'CH_PLASTIC_SLAT', 'CH_MULTI_TIER']),
            'sync_status' => $this->faker->randomElement(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT']),
            'created_at_client' => $this->faker->dateTime(),
            'created_at_server' => $this->faker->optional()->dateTime(),
            'updated_at_client' => $this->faker->dateTime(),
            'updated_at_server' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
        ];
    }
}
