<?php

namespace Database\Factories;

use App\Models\PeriodInvestor;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodInvestorFactory extends Factory
{
    protected $model = PeriodInvestor::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'server_id' => $this->faker->unique()->numberBetween(1, 2147483646),
            'version' => 1,
            'period_id' => $this->faker->uuid(),
            'user_id' => $this->faker->uuid(),
            'profit_share_percentage' => $this->faker->randomFloat(2, 5, 50),
            'initial_investment' => $this->faker->randomFloat(2, 1000000, 10000000),
            'final_dividend_amount' => $this->faker->optional()->randomFloat(2, 100000, 1000000),
            'is_paid' => $this->faker->boolean(),
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => now(),
            'created_at_server' => now(),
            'updated_at_client' => now(),
            'updated_at_server' => now(),
            'deleted_at' => null,
            'sync_metadata' => null,
        ];
    }
}
