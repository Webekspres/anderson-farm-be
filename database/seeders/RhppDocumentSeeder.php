<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RhppDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rhpps = \App\Models\Rhpp::inRandomOrder()->take(5)->get();

        foreach ($rhpps as $rhpp) {
            \App\Models\RhppDocument::factory()->count(2)->create([
                'Rhpp_id' => $rhpp->id,
            ]);
        }
    }
}
