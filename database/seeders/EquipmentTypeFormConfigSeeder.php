<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EquipmentTypeFormConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\EquipmentTypeFormConfig::factory()->count(5)->create();
        // Tambahkan ke DatabaseSeeder agar dijalankan otomatis
    }
}
