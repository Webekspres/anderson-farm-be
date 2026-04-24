<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DailyActivityHeader;

class DailyActivityHeaderSeeder extends Seeder
{
    public function run(): void
    {
        DailyActivityHeader::factory()->count(5)->create();
    }
}
