<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SyncTracker;

class SyncTrackerSeeder extends Seeder
{
    public function run(): void
    {
        SyncTracker::factory()->count(5)->create();
    }
}
