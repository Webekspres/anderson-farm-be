<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\EquipmentType\SyncEquipmentTypeFormConfigRequest;
use App\Models\EquipmentType;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use App\Models\EquipmentTypeFormConfig;

class SyncEquipmentTypeFormConfigController
{
    // Gunakan route model binding agar $equipment_type otomatis adalah model atau null
    public function __invoke(SyncEquipmentTypeFormConfigRequest $request, EquipmentType $equipment_type = null)
    {
        // Workaround: cek guard sanctum agar test 401 benar-benar valid
        if (!auth('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => null,
            ], Response::HTTP_UNAUTHORIZED);
        }
        if (!$equipment_type) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment type not found',
                'data' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $assignments = $request->validated()['form_assignments'];

        DB::transaction(function () use ($equipment_type, $assignments) {
            // Always force delete all assignments for this equipment_type
            EquipmentTypeFormConfig::where('equipment_type_id', $equipment_type->id)->forceDelete();
            // Only insert if assignments is not empty
            if (!empty($assignments)) {
                $now = now();
                $bulk = [];
                foreach ($assignments as $row) {
                    $bulk[] = [
                        'id' => (string)\Illuminate\Support\Str::uuid(),
                        'equipment_type_id' => $equipment_type->id,
                        'form_config_id' => $row['form_config_id'],
                        'display_order' => $row['display_order'],
                        'sync_status' => 'PENDING_SYNC',
                        'created_at_client' => $now,
                        'updated_at_client' => $now,
                    ];
                }
                if ($bulk) {
                    EquipmentTypeFormConfig::insert($bulk);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi form berhasil disinkronisasi',
            'data' => null,
        ], Response::HTTP_OK);
    }
}
