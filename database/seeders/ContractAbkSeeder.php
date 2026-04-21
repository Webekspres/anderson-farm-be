<?php

namespace Database\Seeders;

use App\Models\ContractAbk;
use App\Models\ContractAcceptance;
use Illuminate\Database\Seeder;

class ContractAbkSeeder extends Seeder
{
    public function run(): void
    {
        // Membuat 3 kontrak, di mana masing-class kontrak memiliki 1 persetujuan
        ContractAbk::factory()
            ->count(3)
            ->has(ContractAcceptance::factory()->count(1), 'acceptances')
            ->create();
    }
}
