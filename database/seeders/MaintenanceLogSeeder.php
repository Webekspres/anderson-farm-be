<?php

namespace Database\Seeders;

use App\Models\MaintenanceLog;
use Illuminate\Database\Seeder;

class MaintenanceLogSeeder extends Seeder
{
    public function run(): void
    {
        MaintenanceLog::factory()->count(10)->create();
    }
}
