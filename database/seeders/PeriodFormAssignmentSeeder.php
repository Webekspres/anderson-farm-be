<?php

namespace Database\Seeders;

use App\Models\FormConfig;
use App\Models\PeriodFormAssignment;
use App\Models\ProductionPeriod;
use Illuminate\Database\Seeder;

class PeriodFormAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada period dan form_config
        $periodId = ProductionPeriod::query()->value('id');
        $formConfigIds = FormConfig::query()->pluck('id');

        if ($periodId && $formConfigIds->count()) {
            foreach ($formConfigIds as $idx => $formConfigId) {
                $exists = PeriodFormAssignment::where('period_id', $periodId)
                    ->where('form_config_id', $formConfigId)
                    ->exists();
                if (! $exists) {
                    PeriodFormAssignment::factory()->create([
                        'period_id' => $periodId,
                        'form_config_id' => $formConfigId,
                        'display_order' => $idx + 1,
                    ]);
                }
            }
        }
    }
}
