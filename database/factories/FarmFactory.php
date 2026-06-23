<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

class FarmFactory extends Factory
{
    protected $model = Farm::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 2147483646),
            'version' => 1,
            'area_id' => Area::factory(),
            'name' => 'Farm '.$this->faker->city(),
            'address' => $this->faker->address(),
            'is_active' => $this->faker->boolean(90),
            'sync_status' => $this->faker->randomElement(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT']),
            'created_at_client' => $this->faker->dateTime(),
            'created_at_server' => $this->faker->optional()->dateTime(),
            'updated_at_client' => $this->faker->dateTime(),
            'updated_at_server' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
            'sync_metadata' => null,
            'type' => $this->faker->randomElement(['broiler', 'layer', 'breeder', 'other']),
        ];
    }
}
