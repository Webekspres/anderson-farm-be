<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeSalarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tambahkan seeder ini ke DatabaseSeeder.php jika diperlukan.
        \App\Models\EmployeeSalary::factory()->count(5)->create();
    }
}
