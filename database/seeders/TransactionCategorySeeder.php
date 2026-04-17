<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionCategorySeeder extends Seeder
{
    public function run(): void
    {
        TransactionCategory::factory()->count(10)->create();
    }
}
