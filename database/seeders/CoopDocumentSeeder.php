<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CoopDocument;

class CoopDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CoopDocument::factory()->count(5)->create();
        // Tambahkan pemanggilan seeder ini ke DatabaseSeeder.php jika ingin dijalankan otomatis
    }
}
