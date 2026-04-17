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
        foreach ($formConfigs as $formConfig) {
            // Assign ke period
            PeriodFormAssignment::factory()->create([
                'form_config_id' => $formConfig->id,
                'period_id' => fake()->uuid(), // ganti dengan id period valid jika ada
            ]);
            // Assign ke coop
            CoopFormAssignment::factory()->create([
                'form_config_id' => $formConfig->id,
                'coop_equipment_id' => fake()->uuid(), // ganti dengan id coop_equipment valid jika ada
            ]);
        }
        // Tambahkan ke DatabaseSeeder.php:
        // $this->call(FormConfigSeeder::class);
    }
}
