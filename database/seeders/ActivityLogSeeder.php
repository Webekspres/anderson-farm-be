<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::inRandomOrder()->take(5)->get();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            \App\Models\ActivityLog::factory()->count(3)->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
