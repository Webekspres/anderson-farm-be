<?php

namespace App\Services\Api;

use App\Models\Area;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\Farm;
use App\Models\ProductionPeriod;
use App\Models\Rhpp;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class RhppSyncService
{
    /**
     * Get published RHPP data for offline sync based on user role.
     */
    public function getPullPayload(User $user, ?string $lastSyncTimestamp): Collection
    {
        $query = Rhpp::with('documents')
            ->withTrashed()
            ->where('publish_status', 'PUBLISHED');

        if ($lastSyncTimestamp) {
            $query->where('updated_at_server', '>', $lastSyncTimestamp);
        }

        if ($user->role === 'admin') {
            return $query->get();
        }

        $periodIds = [];

        if (in_array($user->role, ['abk', 'pic'])) {
            $coopIds = CoopUserAssignment::where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->pluck('coop_id');

            $floorIds = CoopFloor::whereIn('coop_id', $coopIds)
                ->whereNull('deleted_at')
                ->pluck('id');

            $periodIds = ProductionPeriod::withTrashed()
                ->whereIn('floor_id', $floorIds)
                ->pluck('id');

        } elseif ($user->role === 'manager') {
            $areaIds = Area::where('manager_id', $user->id)
                ->whereNull('deleted_at')
                ->pluck('id');

            $farmIds = Farm::whereIn('area_id', $areaIds)
                ->whereNull('deleted_at')
                ->pluck('id');

            $coopIds = Coop::whereIn('farm_id', $farmIds)
                ->whereNull('deleted_at')
                ->pluck('id');

            $floorIds = CoopFloor::whereIn('coop_id', $coopIds)
                ->whereNull('deleted_at')
                ->pluck('id');

            $periodIds = ProductionPeriod::withTrashed()
                ->whereIn('floor_id', $floorIds)
                ->pluck('id');
        }

        return $query->whereIn('period_id', $periodIds)->get();
    }
}
