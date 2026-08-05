<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CoopUserAssignment\SyncCoopUserAssignmentRequest;
use App\Http\Resources\Api\V1\CoopUserAssignmentResource;
use App\Models\Coop;
use App\Models\CoopUserAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CoopUserAssignmentController extends Controller
{
    /**
     * Daftar assignment pekerja untuk satu kandang.
     */
    public function index(Coop $coop): JsonResponse
    {
        $assignments = CoopUserAssignment::query()
            ->where('coop_id', $coop->id)
            ->orderBy('assigned_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar assignment kandang berhasil dimuat',
            'data' => CoopUserAssignmentResource::collection($assignments),
        ]);
    }

    /**
     * Sinkronisasi pekerja ke kandang (bulk assignment)
     */
    public function sync(SyncCoopUserAssignmentRequest $request, Coop $coop): JsonResponse
    {
        DB::transaction(function () use ($coop, $request) {
            CoopUserAssignment::withTrashed()
                ->where('coop_id', $coop->id)
                ->forceDelete();

            $now = now();
            $assignments = $request->input('assignments', []);
            foreach ($assignments as $item) {
                CoopUserAssignment::create([
                    'user_id' => $item['user_id'],
                    'coop_id' => $coop->id,
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
