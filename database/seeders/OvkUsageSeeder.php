<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OvkUsage;

class OvkUsageSeeder extends Seeder
{
    public function run(): void
    {
        OvkUsage::factory()->count(5)->create();
    }
}
