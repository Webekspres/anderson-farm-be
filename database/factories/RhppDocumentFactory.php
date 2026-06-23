<?php

namespace Database\Factories;

use App\Models\RhppDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RhppDocument>
 */
class RhppDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'server_id' => $this->faker->unique()->randomNumber(),
            'version' => 1,
            'Rhpp_id' => \App\Models\Rhpp::factory(),
            'name' => $this->faker->word() . '.pdf',
            'file_path_local' => '/storage/app/rhpp/' . $this->faker->word() . '.pdf',
            'file_url' => $this->faker->url(),
            'file_type' => $this->faker->randomElement(['pdf', 'jpg', 'png']),
            'sync_status' => $this->faker->randomElement(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT']),
            'created_at_client' => $this->faker->dateTime(),
            'created_at_server' => $this->faker->optional()->dateTime(),
            'updated_at_client' => $this->faker->dateTime(),
            'updated_at_server' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
        ];
    }
}
