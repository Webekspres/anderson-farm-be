<?php

namespace Database\Seeders;

use App\Models\ChecklistTask;
use App\Models\ProductionPeriod;
use Illuminate\Database\Seeder;

class ChecklistTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada periode yang bisa dikaitkan
        $period = ProductionPeriod::first() ?? ProductionPeriod::factory()->create();

        ChecklistTask::factory()->count(5)->create([
            'period_id' => $period->id,
        ]);
    }
}
