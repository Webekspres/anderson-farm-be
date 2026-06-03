<?php

namespace Database\Seeders;

use App\Models\CoopEquipment;
use App\Models\CoopFormAssignment;
use App\Models\FormConfig;
use App\Models\PeriodFormAssignment;
use App\Models\ProductionPeriod;
use Illuminate\Database\Seeder;

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
        $periodId = ProductionPeriod::query()->value('id');
        $coopEquipmentId = CoopEquipment::query()->value('id');

        foreach ($formConfigs as $formConfig) {
            if ($periodId) {
                $existsPeriod = PeriodFormAssignment::where('period_id', $periodId)
                    ->where('form_config_id', $formConfig->id)
                    ->exists();
                if (! $existsPeriod) {
                    PeriodFormAssignment::factory()->create([
                        'form_config_id' => $formConfig->id,
                        'period_id' => $periodId,
                    ]);
                }
            }
            if ($coopEquipmentId) {
                $existsCoop = CoopFormAssignment::where('coop_equipment_id', $coopEquipmentId)
                    ->where('form_config_id', $formConfig->id)
                    ->exists();
                if (! $existsCoop) {
                    CoopFormAssignment::factory()->create([
                        'form_config_id' => $formConfig->id,
                        'coop_equipment_id' => $coopEquipmentId,
                    ]);
                }
            }
        }
        // Tambahkan ke DatabaseSeeder.php:
        // $this->call(FormConfigSeeder::class);
    }
}
