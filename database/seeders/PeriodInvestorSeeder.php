<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PeriodInvestor;

class PeriodInvestorSeeder extends Seeder
{
    public function run(): void
    {
        PeriodInvestor::factory()->count(5)->create();
    }
}
// Jangan lupa tambahkan PeriodInvestorSeeder::class ke DatabaseSeeder.php
