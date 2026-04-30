<?php

namespace Database\Factories;

use App\Models\MaintenanceLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceLogFactory extends Factory
{
    protected $model = MaintenanceLog::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999),
            'version' => 1,
            'floor_id' => \App\Models\CoopFloor::factory(),
            'reported_by' => \App\Models\User::factory(),
            'date' => $this->faker->dateTime(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['REPORTED', 'IN_PROGRESS', 'RESOLVED']),
            'sync_status' => 'PENDING_SYNC',
            'image_path_local' => null,
            'image_url' => null,
            'created_at_client' => $this->faker->dateTime(),
            'created_at_server' => $this->faker->optional()->dateTime(),
            'updated_at_client' => $this->faker->dateTime(),
            'updated_at_server' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
        ];
    }
}
