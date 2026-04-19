<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CoopUserAssignment;

class CoopUserAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CoopUserAssignment::factory()->count(5)->create();
        // Tambahkan pemanggilan seeder ini ke DatabaseSeeder.php jika ingin dijalankan otomatis
    }
}
