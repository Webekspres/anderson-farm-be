<?php

namespace Database\Factories;

use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractAbkFactory extends Factory
{
    public function definition(): array
    {
        $now = now();
        return [
            'period_id' => ProductionPeriod::factory(),
            'title' => 'Kontrak Kemitraan ' . fake()->monthName(),
            'file_path_local' => null,
            'file_url' => fake()->url() . '/contract.pdf',
            'uploaded_by' => User::factory(),
            'sync_status' => 'SYNCED',
            'created_at_client' => $now,
            'updated_at_client' => $now,
            'created_at_server' => $now,
            'updated_at_server' => $now,
        ];
    }
}
