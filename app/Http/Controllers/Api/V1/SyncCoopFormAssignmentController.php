<?php

namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Coop\SyncCoopFormAssignmentRequest;
use App\Models\Coop;
use App\Models\CoopEquipment;
use App\Models\CoopFormAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SyncCoopFormAssignmentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SyncCoopFormAssignmentRequest $request, $coop)
    {
        // Pastikan coop valid
        $coop = Coop::where('id', $coop)->first();
        if (!$coop) {
            return response()->json([
                'success' => false,
                'message' => 'Kandang tidak ditemukan',
                'data' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $assignments = $request->validated()['assignments'];

        // Ambil semua floor milik kandang ini, lalu ambil semua id alat dari floor-floor tersebut
        $floorIds = $coop->coopFloors()->pluck('id')->toArray();
        $validEquipmentIds = CoopEquipment::whereIn('floor_id', $floorIds)->pluck('id')->toArray();

        // Cek ownership semua coop_equipment_id
        foreach ($assignments as $row) {
            if (!in_array($row['coop_equipment_id'], $validEquipmentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terdapat coop_equipment_id yang tidak valid untuk kandang ini',
                    'data' => null,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        DB::transaction(function () use ($validEquipmentIds, $assignments) {
            // Hapus semua assignment lama untuk alat di kandang ini
            CoopFormAssignment::whereIn('coop_equipment_id', $validEquipmentIds)->forceDelete();

            // Insert ulang assignments baru
            $now = now();
            $bulk = [];
            foreach ($assignments as $row) {
                $bulk[] = [
                    'id' => (string) Str::uuid(),
                    'coop_equipment_id' => $row['coop_equipment_id'],
                    'form_config_id' => $row['form_config_id'],
                    'display_order' => $row['display_order'],
                    'is_active' => $row['is_active'] ?? true,
                    'sync_status' => 'PENDING_SYNC',
                    'created_at_client' => $now,
                    'updated_at_client' => $now,
                    'created_at_server' => $now,
                    'updated_at_server' => $now,
                ];
            }
            if ($bulk) {
                CoopFormAssignment::insert($bulk);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Sinkronisasi form assignment berhasil',
            'data' => null,
        ], Response::HTTP_OK);
    }
}
