<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DailyDynamicLog;

class DailyDynamicLogSeeder extends Seeder
{
    public function run(): void
    {
        DailyDynamicLog::factory()->count(5)->create();
    }
}
