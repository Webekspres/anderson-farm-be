<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
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
            'period_id' => \App\Models\ProductionPeriod::factory(),
            'user_id' => \App\Models\User::factory(),
            'category_id' => \App\Models\TransactionCategory::factory(),
            'harvest_id' => null,
            'salary_id' => null,
            'date' => $this->faker->dateTime(),
            'amount' => $this->faker->randomFloat(2, 10000, 1000000),
            'description' => $this->faker->sentence(),
            'reference_no' => $this->faker->uuid(),
            'receipt_url' => $this->faker->optional()->url(),
            'receipt_path_local' => $this->faker->optional()->filePath(),
            'business_status' => $this->faker->randomElement(['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED']),
            'approved_by' => null,
            'rejection_reason' => null,
            'linked_transaction_id' => null,
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
