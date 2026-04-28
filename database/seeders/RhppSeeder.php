<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RhppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periods = \App\Models\ProductionPeriod::doesntHave('rhpp')->inRandomOrder()->take(5)->get();

        foreach ($periods as $period) {
            \App\Models\Rhpp::factory()->create([
                'period_id' => $period->id,
            ]);
        }
    }
}
