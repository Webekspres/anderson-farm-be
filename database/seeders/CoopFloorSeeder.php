<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CoopFloor;

class CoopFloorSeeder extends Seeder
{
    public function run(): void
    {
        CoopFloor::factory()->count(15)->create();
    }
}
// Tambahkan CoopFloorSeeder ke DatabaseSeeder agar dijalankan otomatis.
