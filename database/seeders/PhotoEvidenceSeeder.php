<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PhotoEvidence;

class PhotoEvidenceSeeder extends Seeder
{
    public function run(): void
    {
        PhotoEvidence::factory()->count(5)->create();
    }
}
