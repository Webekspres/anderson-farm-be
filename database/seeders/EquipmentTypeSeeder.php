<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EquipmentType;

class EquipmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['Traktor', 'Pemanas', 'Tempat Pakan', 'Kipas', 'Sensor Suhu', 'Lampu', 'Pompa Air', 'Alat Ukur PH', 'Dispenser', 'Penyemprot'];
        foreach ($names as $name) {
            EquipmentType::factory()->create(['name' => $name]);
        }
    }
}
