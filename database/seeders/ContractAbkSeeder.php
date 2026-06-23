<?php

namespace Database\Seeders;

use App\Models\ContractAbk;
use App\Models\ContractAcceptance;
use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContractAbkSeeder extends Seeder
{
    public function run(): void
    {
        $periods = ProductionPeriod::query()
            ->where('status', 'active')
            ->with('floor.coop')
            ->orderBy('period_code')
            ->limit(3)
            ->get();

        if ($periods->isEmpty()) {
            $periods = ProductionPeriod::query()
                ->with('floor.coop')
                ->orderBy('period_code')
                ->limit(3)
                ->get();
        }

        if ($periods->isEmpty()) {
            return;
        }

        $uploader = User::query()
            ->whereIn('role', ['manager', 'admin'])
            ->first();

        if (! $uploader) {
            $uploader = User::query()->first();
        }

        if (! $uploader) {
            return;
        }

        $abkUsers = User::query()
            ->where('role', 'abk')
            ->limit(3)
            ->get();

        if ($abkUsers->count() < 3) {
            $needed = 3 - $abkUsers->count();
            $newAbks = User::factory()
                ->count($needed)
                ->create(['role' => 'abk']);

            $abkUsers = $abkUsers->concat($newAbks);
        }

        foreach ($periods->values() as $index => $period) {
            $abk = $abkUsers[$index % $abkUsers->count()];

            $contract = ContractAbk::factory()->create([
                'period_id' => $period->id,
                'uploaded_by' => $uploader->id,
                'title' => sprintf('Kontrak Kemitraan ABK — %s', $period->period_code),
            ]);

            ContractAcceptance::factory()->create([
                'contract_id' => $contract->id,
                'user_id' => $abk->id,
            ]);

            $coopId = $period->floor?->coop_id;

            if ($coopId && ! CoopUserAssignment::query()
                ->where('user_id', $abk->id)
                ->where('coop_id', $coopId)
                ->exists()) {
                CoopUserAssignment::factory()->create([
                    'user_id' => $abk->id,
                    'coop_id' => $coopId,
                    'role_in_coop' => 'abk',
                ]);
            }
        }
    }
}
