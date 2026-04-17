<?php

namespace Database\Factories;

use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionCategoryFactory extends Factory
{
    protected $model = TransactionCategory::class;

    public function definition(): array
    {
        static $usedNames = [];
        $names = [
            'Penjualan Telur',
            'Gaji Pegawai',
            'Pakan Ayam',
            'Obat & Vaksin',
            'Peralatan',
            'Transportasi',
            'Listrik',
            'Air',
            'Pendapatan Lain',
            'Biaya Operasional'
        ];
        $availableNames = array_diff($names, $usedNames);
        if (empty($availableNames)) {
            $name = $this->faker->unique()->lexify('Kategori ???');
        } else {
            $name = $this->faker->randomElement($availableNames);
            $usedNames[] = $name;
        }
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999),
            'version' => 1,
            'name' => $name,
            'type' => $this->faker->randomElement(['INCOME', 'EXPENSE']),
            'is_active' => $this->faker->boolean(90),
            'sync_status' => $this->faker->randomElement(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT']),
            'created_at_client' => now(),
            'created_at_server' => $this->faker->optional()->dateTime(),
            'updated_at_client' => now(),
            'updated_at_server' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
        ];
    }
}
