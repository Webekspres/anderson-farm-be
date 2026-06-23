<?php

namespace Database\Factories;

use App\Models\ChecklistTask;
use App\Models\ProductionPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChecklistTask>
 */
class ChecklistTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $now = now();

        return [
            'id' => Str::uuid()->toString(),
            'server_id' => fake()->unique()->numberBetween(1, 2147483646),
            'version' => 1,
            'period_id' => ProductionPeriod::factory(),
            'task_name' => fake()->randomElement(['Cuci Terpal', 'Cek Suhu Heater', 'Tabur Sekam', 'Catat Kondisi DOC']),
            'task_type' => fake()->randomElement(['BOOLEAN', 'TEXT']),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'sync_status' => 'SYNCED',
            'created_at_client' => $now,
            'created_at_server' => $now,
            'updated_at_client' => $now,
            'updated_at_server' => $now,
        ];
    }
}
