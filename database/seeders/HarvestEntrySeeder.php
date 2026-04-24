<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HarvestEntry;

class HarvestEntrySeeder extends Seeder
{
    public function run(): void
    {
        HarvestEntry::factory()->count(5)->create();
    }
}
