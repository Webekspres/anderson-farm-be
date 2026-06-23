<?php

namespace Database\Factories;

use App\Models\FormConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormConfigFactory extends Factory
{
    protected $model = FormConfig::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 2147483646),
            'version' => 1,
            'category' => $this->faker->randomElement(['EQUIPMENT', 'HBE']),
            'key_name' => $this->faker->unique()->regexify('([a-z_]{5,15})'),
            'config_value' => [
                'type' => $this->faker->randomElement(['number', 'scale', 'text']),
                'label' => $this->faker->words(2, true),
                'required' => $this->faker->boolean(),
                'min' => $this->faker->numberBetween(0, 10),
                'max' => $this->faker->numberBetween(10, 100),
                'unit' => $this->faker->randomElement(['Celcius', 'Kg', 'L', 'Unit']),
            ],
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => $this->faker->dateTime,
            'created_at_server' => $this->faker->optional()->dateTime,
            'updated_at_client' => $this->faker->dateTime,
            'updated_at_server' => $this->faker->optional()->dateTime,
            'deleted_at' => null,
        ];
    }
}
