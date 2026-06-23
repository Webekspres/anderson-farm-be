<?php

namespace Database\Factories;

use App\Models\Coop;
use App\Models\CoopUserAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoopUserAssignment>
 */
class CoopUserAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dateRange = '-1 year';

        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 2147483646),
            'version' => 1,
            'user_id' => User::factory(),
            'coop_id' => Coop::factory(),
            'assigned_at' => $this->faker->dateTimeBetween($dateRange, 'now'),
            'role_in_coop' => $this->faker->randomElement(['MANAGER', 'PIC', 'ABK', null]),
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => $this->faker->dateTimeBetween($dateRange, 'now'),
            'created_at_server' => null,
            'updated_at_client' => $this->faker->dateTimeBetween($dateRange, 'now'),
            'updated_at_server' => null,
            'deleted_at' => null,
        ];
    }
}
