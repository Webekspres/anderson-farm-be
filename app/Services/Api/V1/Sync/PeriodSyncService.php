<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Sync;

use App\Models\ContractAbk;
use App\Models\ContractAcceptance;
use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use App\Models\User;
use App\Support\CoopAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodSyncService
{
    public function getPeriodDetail(string $periodId, ?string $lastSyncTimestamp, User $user): ?ProductionPeriod
    {
        $basePeriod = ProductionPeriod::withTrashed()->findOrFail($periodId);

        // Get coop through floor
        $coop = $basePeriod->floor->coop;

        if (! CoopAccess::canAccessCoop($user, $coop?->id)) {
            abort(403, 'Akses ditolak untuk periode ini.');
        }

        $timestamp = $lastSyncTimestamp
            ? Carbon::parse($lastSyncTimestamp)->setTimezone(config('app.timezone'))
            : null;

        $isPrivileged = in_array($user->role, ['admin', 'manager', 'finance', 'pic'], true);

        $periodQuery = ProductionPeriod::withTrashed()
            ->where('id', $periodId)
            ->with([
                'investors' => function ($query) use ($user, $isPrivileged, $timestamp) {
                    if (! $isPrivileged) {
                        $query->where('user_id', $user->id);
                    }
                    if ($timestamp) {
                        $query->where('updated_at_server', '>', $timestamp);
                    }
                    $query->withTrashed();
                },
                'salaries' => function ($query) use ($user, $timestamp) {
                    $query->where('employee_id', $user->id);
                    if ($timestamp) {
                        $query->where('updated_at_server', '>', $timestamp);
                    }
                    $query->withTrashed();
                },
                'contracts' => function ($query) use ($timestamp) {
                    if ($timestamp) {
                        $query->where('updated_at_server', '>', $timestamp);
                    }
                    $query->withTrashed()->with([
                        'acceptances' => function ($acceptanceQuery) use ($timestamp) {
                            if ($timestamp) {
                                $acceptanceQuery->where('updated_at_server', '>', $timestamp);
                            }
                            $acceptanceQuery->withTrashed();
                        },
                    ]);
                },
                'documents' => function ($query) use ($timestamp) {
                    if ($timestamp) {
                        $query->where('updated_at_server', '>', $timestamp);
                    }
                    $query->withTrashed();
                },
            ]);

        if ($timestamp) {
            $periodQuery->where('updated_at_server', '>', $timestamp);
        }

        return $periodQuery->first();
    }

    /**
     * Store contract acceptances from mobile app.
     *
     * This is a "push" endpoint where ABK/PIC send their digital signatures.
     * Uses idempotency (updateOrCreate) to handle duplicate submissions.
     *
     * @param  User  $user  The authenticated user (ABK/PIC)
     * @param  array  $acceptances  Array of contract acceptance items
     * @param  string  $syncTimestamp  Client sync timestamp (ISO-8601)
     * @return array Sync results: [{ id, status: SUCCESS|FAILED|FORBIDDEN }, ...]
     */
    public function storeContractAcceptances(User $user, array $acceptances, string $syncTimestamp): array
    {
        return DB::transaction(function () use ($user, $acceptances): array {
            $syncResults = [];

            foreach ($acceptances as $item) {
                $acceptanceId = $item['id'];
                $contractId = $item['contract_id'];
                $acceptedAt = $item['accepted_at'];

                $contract = ContractAbk::query()->find($contractId);
                if (! $contract) {
                    $syncResults[] = [
                        'id' => $acceptanceId,
                        'status' => 'FAILED',
                    ];

                    continue;
                }

                $period = $contract->period;
                $coop = $period?->floor?->coop;
                if (! $coop) {
                    $syncResults[] = [
                        'id' => $acceptanceId,
                        'status' => 'FAILED',
                    ];

                    continue;
                }

                $hasAssignment = CoopUserAssignment::query()
                    ->where('user_id', $user->id)
                    ->where('coop_id', $coop->id)
                    ->exists();

                if (! $hasAssignment) {
                    $syncResults[] = [
                        'id' => $acceptanceId,
                        'status' => 'FORBIDDEN',
                    ];

                    continue;
                }

                ContractAcceptance::updateOrCreate(
                    [
                        'contract_id' => $contractId,
                        'user_id' => $user->id,
                    ],
                    [
                        'id' => $acceptanceId,
                        'accepted_at' => Carbon::parse($acceptedAt),
                    ]
                );

                $syncResults[] = [
                    'id' => $acceptanceId,
                    'status' => 'SUCCESS',
                ];
            }

            return $syncResults;
        });
    }
}
