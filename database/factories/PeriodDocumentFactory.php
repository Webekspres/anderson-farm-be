<?php

namespace Database\Factories;

use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodDocumentFactory extends Factory
{
    public function definition(): array
    {
        $now = now();

        return [
            'period_id' => ProductionPeriod::factory(),
            'title' => 'Surat Jalan Pakan '.fake()->date(),
            'document_type' => fake()->randomElement(['OVK', 'ARV', 'OTHER', 'CARE_TEMPLATE']),
            'file_path_local' => null,
            'file_url' => fake()->url().'/surat-jalan.jpg',
            'uploaded_by' => User::factory(),
            'sync_status' => 'SYNCED',
            'created_at_client' => $now,
            'updated_at_client' => $now,
        ];
    }
}
