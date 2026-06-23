<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\ReportTemplate::factory()->count(5)->create();
        // Tambahkan pemanggilan seeder ini ke DatabaseSeeder.php jika ingin seed otomatis
    }
}
