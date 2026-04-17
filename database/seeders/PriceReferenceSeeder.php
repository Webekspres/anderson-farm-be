<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PriceReference;

class PriceReferenceSeeder extends Seeder
{
    public function run(): void
    {
        PriceReference::factory()->count(10)->create();
    }
}
