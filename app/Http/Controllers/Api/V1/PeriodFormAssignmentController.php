<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Period\SyncPeriodFormAssignmentRequest;
use App\Http\Resources\Api\V1\PeriodFormAssignmentResource;
use App\Models\PeriodFormAssignment;
use App\Models\ProductionPeriod;
use Illuminate\Support\Facades\DB;

class PeriodFormAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    public function index($period_id)
    {
        $assignments = PeriodFormAssignment::with('formConfig')
            ->where('period_id', $period_id)
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'List of form assignments for period.',
            'data' => PeriodFormAssignmentResource::collection($assignments),
        ]);
    }

    public function sync(SyncPeriodFormAssignmentRequest $request, $period_id)
    {
        $period = ProductionPeriod::findOrFail($period_id);
        $assignments = $request->validated()['assignments'];

        DB::transaction(function () use ($period, $assignments) {
            PeriodFormAssignment::where('period_id', $period->id)->forceDelete();

            $now = now();
            foreach ($assignments as $row) {
                PeriodFormAssignment::create([
                    'period_id' => $period->id,
                    'form_config_id' => $row['form_config_id'],
                    'display_order' => $row['display_order'],
                    'is_active' => $row['is_active'],
                    'sync_status' => 'PENDING_SYNC',
                    'created_at_client' => $now,
                    'created_at_server' => $now,
                    'updated_at_client' => $now,
                    'updated_at_server' => $now,
                ]);
            }
        });

        $result = PeriodFormAssignment::with('formConfig')
            ->where('period_id', $period->id)
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Assignments synced.',
            'data' => PeriodFormAssignmentResource::collection($result),
        ]);
    }
}
