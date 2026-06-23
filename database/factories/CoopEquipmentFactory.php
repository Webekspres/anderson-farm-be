<?php

namespace Database\Factories;

use App\Models\CoopEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoopEquipment>
 */
class CoopEquipmentFactory extends Factory
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
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999),
            'version' => 1,
            'floor_id' => fn() => \App\Models\CoopFloor::factory(),
            'equipment_type_id' => fn() => \App\Models\EquipmentType::factory(),
            'unit_code' => $this->faker->optional()->bothify('UNIT-####'),
            'installed_at' => $this->faker->optional()->dateTime(),
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => now(),
            'created_at_server' => now(),
            'updated_at_client' => now(),
            'updated_at_server' => now(),
            'deleted_at' => null,
        ];
    }
}
