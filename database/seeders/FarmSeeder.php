<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Farm;

class FarmSeeder extends Seeder
{
    public function run(): void
    {
        Farm::factory()->count(10)->create();
    }
}
// Tambahkan FarmSeeder ke DatabaseSeeder.php agar dijalankan otomatis.
