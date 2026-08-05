<?php

namespace App\Services\Api\V1\Sync;

use App\Models\Area;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\EducationArticle;
use App\Models\EquipmentType;
use App\Models\Farm;
use App\Models\FormConfig;
use App\Models\OvkItem;
use App\Models\PriceReference;
use App\Models\ProductionPeriod;
use App\Models\ReportTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class MasterDataSyncService
{
    /**
     * Roles that receive the full farm/coop/period hierarchy (not assignment-scoped).
     *
     * @var list<string>
     */
    private const FULL_HIERARCHY_ROLES = ['admin', 'manager', 'finance'];

    /**
     * Hierarchy keys always returned in full for the user's scope (no delta filter).
     *
     * @var list<string>
     */
    private const HIERARCHY_KEYS = [
        'coop_user_assignments',
        'coops',
        'farms',
        'areas',
        'production_periods',
    ];

    /**
     * Mengkompilasi semua data master (hierarki & global) berdasarkan timestamp terakhir sinkronisasi.
     *
     * Hierarki selalu full-set untuk scope user agar Pilih Konteks tidak kosong setelah delta sync
     * atau untuk role admin/manager/finance tanpa coop_user_assignments.
     * Katalog global tetap mendukung delta via last_sync_timestamp.
     */
    public function compileMasterData(?string $lastSyncTimestamp, User $user): array
    {
        $useFullHierarchy = $this->receivesFullHierarchy($user);

        if ($useFullHierarchy) {
            $allCoopIds = Coop::query()->pluck('id')->all();
            $allFarmIds = Farm::query()->pluck('id')->all();
            $allAreaIds = Area::query()->pluck('id')->all();
            $allFloorIds = CoopFloor::query()->pluck('id')->all();
        } else {
            $allCoopIds = CoopUserAssignment::query()
                ->where('user_id', $user->id)
                ->pluck('coop_id')
                ->all();

            $allFarmIds = empty($allCoopIds)
                ? []
                : Coop::query()->whereIn('id', $allCoopIds)->pluck('farm_id')->all();

            $allAreaIds = empty($allFarmIds)
                ? []
                : Farm::query()->whereIn('id', $allFarmIds)->pluck('area_id')->all();

            $allFloorIds = empty($allCoopIds)
                ? []
                : CoopFloor::query()->whereIn('coop_id', $allCoopIds)->pluck('id')->all();
        }

        $queries = [
            'coop_user_assignments' => $useFullHierarchy
                ? CoopUserAssignment::query()
                : CoopUserAssignment::query()->where('user_id', $user->id),
            'coops' => empty($allCoopIds)
                ? Coop::query()->whereRaw('0 = 1')
                : Coop::query()->whereIn('id', $allCoopIds),
            'farms' => empty($allFarmIds)
                ? Farm::query()->whereRaw('0 = 1')
                : Farm::query()->whereIn('id', $allFarmIds),
            'areas' => empty($allAreaIds)
                ? Area::query()->whereRaw('0 = 1')
                : Area::query()->whereIn('id', $allAreaIds),
            'production_periods' => empty($allFloorIds)
                ? ProductionPeriod::query()->whereRaw('0 = 1')
                : ProductionPeriod::query()
                    ->whereIn('floor_id', $allFloorIds)
                    ->with(['floor:id,coop_id,name']),
            'form_configs' => FormConfig::query(),
            'equipment_types' => EquipmentType::query(),
            'ovk_items' => OvkItem::query(),
            'education_articles' => EducationArticle::query(),
            'price_references' => PriceReference::query(),
            'report_templates' => ReportTemplate::query(),
        ];

        $parsedTimestamp = $lastSyncTimestamp
            ? Carbon::parse($lastSyncTimestamp)->setTimezone(config('app.timezone'))
            : null;

        $result = [];

        foreach ($queries as $key => $query) {
            $isHierarchy = in_array($key, self::HIERARCHY_KEYS, true);

            if ($parsedTimestamp !== null && ! $isHierarchy) {
                $this->applyDeltaFilter($query, $parsedTimestamp);
            }

            $result[$key] = $query->get();
        }

        return $result;
    }

    private function receivesFullHierarchy(User $user): bool
    {
        return in_array((string) $user->role, self::FULL_HIERARCHY_ROLES, true);
    }

    private function applyDeltaFilter(Builder $query, Carbon $parsedTimestamp): void
    {
        $query->where('updated_at_server', '>', $parsedTimestamp);

        $model = $query->getModel();
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model), true)) {
            $query->withTrashed();
        }
    }
}
