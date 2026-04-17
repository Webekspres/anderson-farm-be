<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coop;

class CoopSeeder extends Seeder
{
    public function run(): void
    {
        Coop::factory()->count(10)->create();
    }
}
// Tambahkan CoopSeeder ke DatabaseSeeder agar dijalankan otomatis.
