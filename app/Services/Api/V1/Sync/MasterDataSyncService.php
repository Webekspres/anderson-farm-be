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

class MasterDataSyncService
{
    /**
     * Mengkompilasi semua data master (hierarki & global) berdasarkan timestamp terakhir sinkronisasi.
     */
    public function compileMasterData(?string $lastSyncTimestamp, User $user): array
    {
        // 1. Ekstraksi Hierarki Dasar (Tanpa filter timestamp untuk mendapatkan struktur lengkap)
        $allCoopIds = CoopUserAssignment::where('user_id', $user->id)
            ->pluck('coop_id')
            ->toArray();

        $allFarmIds = empty($allCoopIds) ? [] : Coop::whereIn('id', $allCoopIds)
            ->pluck('farm_id')
            ->toArray();

        $allAreaIds = empty($allFarmIds) ? [] : Farm::whereIn('id', $allFarmIds)
            ->pluck('area_id')
            ->toArray();

        // Get all floors for the coops
        $allFloorIds = empty($allCoopIds) ? [] : CoopFloor::whereIn('coop_id', $allCoopIds)
            ->pluck('id')
            ->toArray();

        // 2. Siapkan Query Dasar
        $queries = [
            'coop_user_assignments' => CoopUserAssignment::where('user_id', $user->id),
            'coops' => Coop::whereIn('id', $allCoopIds),
            'farms' => Farm::whereIn('id', $allFarmIds),
            'areas' => Area::whereIn('id', $allAreaIds),
            'production_periods' => ProductionPeriod::whereIn('floor_id', $allFloorIds),
            'form_configs' => FormConfig::query(),
            'equipment_types' => EquipmentType::query(),
            'ovk_items' => OvkItem::query(),
            'education_articles' => EducationArticle::query(),
            'price_references' => PriceReference::query(),
            'report_templates' => ReportTemplate::query(),
        ];

        $result = [];

        // 3. Terapkan Filter Delta Sync & Soft Deletes
        $parsedTimestamp = $lastSyncTimestamp ? Carbon::parse($lastSyncTimestamp)->setTimezone(config('app.timezone')) : null;

        foreach ($queries as $key => $query) {
            if ($parsedTimestamp) {
                $query->where('updated_at_server', '>', $parsedTimestamp);

                // Cek apakah model menggunakan trait SoftDeletes
                $model = $query->getModel();
                if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model))) {
                    $query->withTrashed();
                }
            }
            $result[$key] = $query->get();
        }

        return $result;
    }
}
