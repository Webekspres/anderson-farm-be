<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FormConfig;
use App\Models\PeriodFormAssignment;
use App\Models\CoopFormAssignment;

class FormConfigSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat 5 FormConfig (campuran kategori HBE dan EQUIPMENT)
        $formConfigs = FormConfig::factory()->count(5)->sequence(
            ['category' => 'EQUIPMENT'],
            ['category' => 'HBE'],
            ['category' => 'EQUIPMENT'],
            ['category' => 'HBE'],
            ['category' => 'EQUIPMENT'],
        )->create();

        // 2. Dummy assignments ke period/coop
        // Cek data period dan coop_equipment valid
        $periodId = \App\Models\ProductionPeriod::query()->value('id');
        $coopEquipmentId = \App\Models\CoopEquipment::query()->value('id');

        foreach ($formConfigs as $formConfig) {
            if ($periodId) {
                PeriodFormAssignment::factory()->create([
                    'form_config_id' => $formConfig->id,
                    'period_id' => $periodId,
                ]);
            }
            if ($coopEquipmentId) {
                CoopFormAssignment::factory()->create([
                    'form_config_id' => $formConfig->id,
                    'coop_equipment_id' => $coopEquipmentId,
                ]);
            }
        }
        // Tambahkan ke DatabaseSeeder.php:
        // $this->call(FormConfigSeeder::class);
    }
}
