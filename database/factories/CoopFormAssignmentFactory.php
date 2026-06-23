<?php

namespace Database\Factories;

use App\Models\CoopFormAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoopFormAssignmentFactory extends Factory
{
    protected $model = CoopFormAssignment::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999),
            'coop_equipment_id' => $this->faker->uuid(),
            'form_config_id' => $this->faker->uuid(),
            'display_order' => $this->faker->numberBetween(1, 10),
            'is_active' => $this->faker->boolean(90),
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => $this->faker->dateTime,
            'created_at_server' => $this->faker->optional()->dateTime,
            'updated_at_client' => $this->faker->dateTime,
            'updated_at_server' => $this->faker->optional()->dateTime,
            'deleted_at' => null,
        ];
    }
}
