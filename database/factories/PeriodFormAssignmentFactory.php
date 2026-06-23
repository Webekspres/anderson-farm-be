<?php

namespace Database\Factories;

use App\Models\FormConfig;
use App\Models\PeriodFormAssignment;
use App\Models\ProductionPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodFormAssignmentFactory extends Factory
{
    protected $model = PeriodFormAssignment::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 2147483646),
            'period_id' => ProductionPeriod::factory(),
            'form_config_id' => FormConfig::factory(),
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
