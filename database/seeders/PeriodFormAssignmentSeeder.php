<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PeriodFormAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada period dan form_config
        $periodId = \App\Models\ProductionPeriod::query()->value('id');
        $formConfigIds = \App\Models\FormConfig::query()->pluck('id');

        if ($periodId && $formConfigIds->count()) {
            foreach ($formConfigIds as $idx => $formConfigId) {
                \App\Models\PeriodFormAssignment::factory()->create([
                    'period_id' => $periodId,
                    'form_config_id' => $formConfigId,
                    'display_order' => $idx + 1,
                ]);
            }
        }
    }
}
