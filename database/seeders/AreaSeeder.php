<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        Area::factory()->count(5)->create();
    }
}
// Tambahkan AreaSeeder ke DatabaseSeeder.php agar dijalankan otomatis.
