<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EquipmentType\SyncEquipmentTypeFormConfigRequest;
use App\Models\EquipmentType;
use App\Models\EquipmentTypeFormConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

class SyncEquipmentTypeFormConfigController extends Controller
{
    public function __invoke(SyncEquipmentTypeFormConfigRequest $request, $equipment_type_id)
    {
        $equipmentType = EquipmentType::where('id', $equipment_type_id)->first();
        if (!$equipmentType) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment type tidak ditemukan',
                'data' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $assignments = $request->validated()['form_assignments'];

        DB::transaction(function () use ($equipmentType, $assignments) {
            EquipmentTypeFormConfig::where('equipment_type_id', $equipmentType->id)->forceDelete();
            if (is_array($assignments) && count($assignments) > 0) {
                $now = now();
                $bulk = [];
                foreach ($assignments as $row) {
                    $bulk[] = [
                        'id' => (string)\Illuminate\Support\Str::uuid(),
                        'equipment_type_id' => $equipmentType->id,
                        'form_config_id' => $row['form_config_id'],
                        'display_order' => $row['display_order'],
                        'sync_status' => 'PENDING_SYNC',
                        'created_at_client' => $now,
                        'created_at_server' => $now,
                        'updated_at_client' => $now,
                        'updated_at_server' => $now,
                        'version' => 1,
                    ];
                }
                if ($bulk) {
                    EquipmentTypeFormConfig::insert($bulk);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Master SOP alat berhasil diperbarui',
            'data' => null,
        ], Response::HTTP_OK);
    }
}
