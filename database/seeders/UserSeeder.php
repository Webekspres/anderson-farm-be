<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::factory()->make();
        $user->username = 'admin';
        $user->password_hash = \Illuminate\Support\Facades\Hash::make('password123');
        $user->name = 'Administrator';
        $user->email = 'admin@example.com';
        $user->phone_number = '08123456789';
        $user->role = 'admin';
        $user->server_id = 1;
        $user->save();
    }
}
