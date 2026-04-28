<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
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
            'user_id' => \App\Models\User::factory(),
            'action' => $this->faker->word(),
            'entity_type' => $this->faker->word(),
            'entity_id' => (string) \Illuminate\Support\Str::uuid(),
            'device_id' => $this->faker->uuid(),
            'status' => $this->faker->randomElement(['SUCCESS', 'FAILED']),
            'metadata' => json_encode(['key' => 'value']),
            'sync_status' => $this->faker->randomElement(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT']),
            'created_at_client' => $this->faker->dateTime(),
            'created_at_server' => $this->faker->optional()->dateTime(),
            'updated_at_client' => $this->faker->dateTime(),
            'updated_at_server' => $this->faker->optional()->dateTime(),
            'deleted_at' => null,
            'sync_metadata' => null,
        ];
    }
}
