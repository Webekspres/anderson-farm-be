<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Sync;

use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PeriodSyncService
{
    public function getPeriodDetail(string $periodId, ?string $lastSyncTimestamp, User $user): ?ProductionPeriod
    {
        $basePeriod = ProductionPeriod::withTrashed()->findOrFail($periodId);

        // Get coop through floor
        $coop = $basePeriod->floor->coop;

        $hasAccess = CoopUserAssignment::where('user_id', $user->id)
            ->where('coop_id', $coop->id)
            ->exists();

        if (!$hasAccess) {
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
                    if (!$isPrivileged) {
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
}
