<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OvkItem;

class OvkItemSeeder extends Seeder
{
    public function run(): void
    {
        OvkItem::factory()->count(15)->create();
    }
}
// Tambahkan OvkItemSeeder ke DatabaseSeeder agar dijalankan otomatis.
