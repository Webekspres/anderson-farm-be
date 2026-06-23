<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periods = \App\Models\ProductionPeriod::inRandomOrder()->take(5)->get();
        $users = \App\Models\User::inRandomOrder()->take(5)->get();
        $categories = \App\Models\TransactionCategory::inRandomOrder()->take(5)->get();

        if ($periods->isEmpty() || $users->isEmpty() || $categories->isEmpty()) return;

        foreach ($periods as $period) {
            \App\Models\Transaction::factory()->count(3)->create([
                'period_id' => $period->id,
                'user_id' => $users->random()->id,
                'category_id' => $categories->random()->id,
            ]);
        }
    }
}
