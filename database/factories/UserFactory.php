<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id'            => fake()->uuid(),
            'server_id'     => fake()->unique()->numberBetween(1, 999999),
            'username' => fake()->unique()->userName(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->phoneNumber(),
            'role' => fake()->randomElement(['admin', 'manager', 'finance', 'pic', 'abk', 'investor']),
            'device_id' => null,
            'device_bound_at' => null,
            'is_active' => true,
            'version' => 1,
            'last_validated_at' => null,
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => now(),
            'created_at_server' => now(),
            'updated_at_client' => now(),
            'updated_at_server' => now(),
            'deleted_at' => null,
            'sync_metadata' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
