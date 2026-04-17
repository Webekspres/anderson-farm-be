<?php

namespace Database\Factories;

use App\Models\EquipmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentTypeFactory extends Factory
{
    protected $model = EquipmentType::class;

    public function definition(): array
    {
        $names = ['Traktor', 'Pemanas', 'Tempat Pakan', 'Kipas', 'Sensor Suhu', 'Lampu', 'Pompa Air', 'Alat Ukur PH', 'Dispenser', 'Penyemprot'];
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999),
            'version' => 1,
            'name' => $this->faker->randomElement($names),
            'description' => $this->faker->optional()->sentence(),
            'sync_status' => $this->faker->randomElement(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT']),
            'created_at_client' => now(),
            'created_at_server' => $this->faker->optional()->dateTime(),
            'updated_at_client' => now(),
            'updated_at_server' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
        ];
    }
}
