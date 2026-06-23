<?php

namespace Database\Factories;

use App\Models\ContractAbk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContractAcceptanceFactory extends Factory
{
    public function definition(): array
    {
        $now = now();

        return [
            'id' => Str::uuid()->toString(),
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999999),
            'contract_id' => ContractAbk::factory(),
            'user_id' => User::factory()->state(['role' => 'abk']),
            'accepted_at' => $now,
            'device_id' => Str::random(16),
            'current_loan_accumulated' => $this->faker->randomFloat(2, 0, 1000000),
            'remaining_loan_limit' => $this->faker->randomFloat(2, 1000000, 3000000),
            'sync_status' => 'SYNCED',
            'created_at_client' => $now,
            'updated_at_client' => $now,
            'created_at_server' => $now,
            'updated_at_server' => $now,
        ];
    }
}
