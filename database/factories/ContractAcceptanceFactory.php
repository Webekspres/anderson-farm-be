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
            'contract_id' => ContractAbk::factory(),
            'user_id' => User::factory(),
            'accepted_at' => $now,
            'device_id' => Str::random(16),
            'sync_status' => 'SYNCED',
            'created_at_client' => $now,
            'updated_at_client' => $now,
            'created_at_server' => $now,
            'updated_at_server' => $now,
        ];
    }
}
