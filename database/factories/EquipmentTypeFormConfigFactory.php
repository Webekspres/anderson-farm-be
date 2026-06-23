<?php

namespace Database\Factories;

use App\Models\EquipmentType;
use App\Models\EquipmentTypeFormConfig;
use App\Models\FormConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentTypeFormConfig>
 */
class EquipmentTypeFormConfigFactory extends Factory
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
            'equipment_type_id' => fn () => EquipmentType::factory(),
            'form_config_id' => fn () => FormConfig::factory(),
            'display_order' => $this->faker->numberBetween(1, 10),
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => now(),
            'created_at_server' => now(),
            'updated_at_client' => now(),
            'updated_at_server' => now(),
            'deleted_at' => null,
        ];
    }
}
