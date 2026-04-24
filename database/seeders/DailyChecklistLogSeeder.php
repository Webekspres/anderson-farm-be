<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DailyChecklistLog;

class DailyChecklistLogSeeder extends Seeder
{
    public function run(): void
    {
        DailyChecklistLog::factory()->count(5)->create();
    }
}
