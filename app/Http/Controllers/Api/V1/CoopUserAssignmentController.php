<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Coop;
use App\Models\CoopUserAssignment;
use App\Http\Requests\Api\V1\CoopUserAssignment\SyncCoopUserAssignmentRequest;

class CoopUserAssignmentController extends Controller
{
    /**
     * Sinkronisasi pekerja ke kandang (bulk assignment)
     */
    public function sync(SyncCoopUserAssignmentRequest $request, $coop_id)
    {
        $coop = Coop::find($coop_id);
        if (!$coop) {
            return response()->json([
                'success' => false,
                'message' => 'Kandang tidak ditemukan',
                'data' => null,
            ], 404);
        }

        DB::transaction(function () use ($coop_id, $request) {
            CoopUserAssignment::where('coop_id', $coop_id)->delete();

            $now = now();
            $assignments = $request->input('assignments', []);
            foreach ($assignments as $item) {
                CoopUserAssignment::create([
                    'user_id' => $item['user_id'],
                    'coop_id' => $coop_id,
                    'assigned_at' => $now,
                    'role_in_coop' => $item['role_in_coop'] ?? null,
                    'sync_status' => 'PENDING_SYNC',
                    'created_at_client' => $now,
                    'updated_at_client' => $now,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Pekerja berhasil ditugaskan ke kandang',
            'data' => null,
        ]);
    }
}
