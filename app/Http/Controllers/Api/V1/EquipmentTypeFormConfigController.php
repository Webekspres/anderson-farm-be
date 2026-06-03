<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EquipmentType\SyncEquipmentFormConfigsRequest;
use App\Http\Resources\Api\V1\EquipmentFormAssignmentResource;
use App\Models\EquipmentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EquipmentTypeFormConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function getFormConfigs(string $equipment_type): JsonResponse
    {
        $equipmentType = EquipmentType::query()->findOrFail($equipment_type);

        $formConfigs = $equipmentType->formConfigs()
            ->orderBy('equipment_type_form_configs.display_order')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi form untuk tipe alat berhasil diambil.',
            'data' => EquipmentFormAssignmentResource::collection($formConfigs),
        ]);
    }

    public function syncFormConfigs(SyncEquipmentFormConfigsRequest $request, string $equipment_type): JsonResponse
    {
        $equipmentType = EquipmentType::query()->findOrFail($equipment_type);
        $formConfigIds = $request->validated('form_config_ids');

        DB::transaction(function () use ($equipmentType, $formConfigIds): void {
            $now = now();
            $syncPayload = [];

            foreach ($formConfigIds as $index => $formConfigId) {
                $syncPayload[$formConfigId] = [
                    'id' => (string) Str::uuid(),
                    'display_order' => $index + 1,
                    'sync_status' => 'SYNCED',
                    'created_at_client' => $now,
                    'created_at_server' => $now,
                    'updated_at_client' => $now,
                    'updated_at_server' => $now,
                    'version' => 1,
                ];
            }

            $equipmentType->formConfigs()->sync($syncPayload);
        });

        $formConfigs = $equipmentType->formConfigs()
            ->orderBy('equipment_type_form_configs.display_order')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Pemetaan form tipe alat berhasil disinkronkan.',
            'data' => EquipmentFormAssignmentResource::collection($formConfigs),
        ]);
    }
}
