<?php

namespace Database\Factories;

use App\Models\EmployeeSalary;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeSalary>
 */
class EmployeeSalaryFactory extends Factory
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
            'period_id' => ProductionPeriod::factory(),
            'employee_id' => User::factory()->state(['role' => 'abk']),
            'salary_amount' => $this->faker->randomFloat(2, 100000, 5000000),
            'payment_status' => $this->faker->randomElement(['draft', 'paid']),
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => now(),
            'created_at_server' => now(),
            'updated_at_client' => now(),
            'updated_at_server' => now(),
            'deleted_at' => null,
            'sync_metadata' => null,
        ];
    }
}
