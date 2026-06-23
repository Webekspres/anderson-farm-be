<?php

namespace Database\Factories;

use App\Models\ProductionPeriod;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'id' => (string) Str::uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 9999999999),
            'version' => 1,
            'period_id' => ProductionPeriod::factory(),
            'coop_id' => null,
            'user_id' => User::factory(),
            'category_id' => TransactionCategory::factory(),
            'harvest_id' => null,
            'salary_id' => null,
            'date' => $this->faker->dateTime(),
            'amount' => $this->faker->randomFloat(2, 10000, 1000000),
            'description' => $this->faker->sentence(),
            'reference_no' => $this->faker->uuid(),
            'receipt_url' => $this->faker->optional()->url(),
            'receipt_path_local' => $this->faker->optional()->filePath(),
            'expense_scope' => $this->faker->randomElement(['FLOOR_SPECIFIC', 'COOP_SHARED']),
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
