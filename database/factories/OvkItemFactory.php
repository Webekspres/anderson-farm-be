<?php

namespace Database\Factories;

use App\Models\OvkItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OvkItemFactory extends Factory
{
    protected $model = OvkItem::class;

    public function definition(): array
    {
        $names = [
            'Vaksin ND',
            'Disinfektan',
            'Vitamin C',
            'Vita Stress',
            'Antibiotik',
            'Vaksin AI',
            'Probiotik',
            'Desinfektan Kandang',
            'Vitamin ADE',
            'Obat Cacing',
            'Vaksin Gumboro',
            'Kalsium',
            'Mineral Mix',
            'Obat Luka',
            'Vaksin Marek'
        ];
        $units = ['ml', 'gram', 'botol', 'sachet', 'tablet', 'kg'];
        static $usedNames = [];
        $availableNames = array_diff($names, $usedNames);
        if (empty($availableNames)) {
            $name = $this->faker->unique()->lexify('OVK ???');
        } else {
            $name = $this->faker->randomElement($availableNames);
            $usedNames[] = $name;
        }
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999),
            'version' => 1,
            'name' => $name,
            'type' => $this->faker->randomElement(['OBAT', 'VAKSIN', 'KIMIA']),
            'unit' => $this->faker->randomElement($units),
            'description' => $this->faker->optional()->sentence(6),
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
