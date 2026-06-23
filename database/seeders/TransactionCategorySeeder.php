<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        TransactionCategory::query()->updateOrCreate(
            ['name' => 'Gaji Pegawai'],
            [
                'type' => 'EXPENSE',
                'is_active' => true,
                'sync_status' => 'SYNCED',
                'created_at_client' => $now,
                'updated_at_server' => $now,
                'updated_at_client' => $now,
                'updated_at_server' => $now,
                'deleted_at' => null,
            ],
        );

        TransactionCategory::factory()->count(9)->create();
    }
}
