<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePeriodRequest;
use App\Http\Resources\Api\V1\ProductionPeriodResource;
use App\Models\ProductionPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PeriodController extends Controller
{
    public function store(StorePeriodRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            // Generate period_code jika null
            $periodCode = $validated['period_code'] ?? (
                'PRD-' . now()->format('Ym') . '-' . strtoupper(substr($validated['coop_id'], 0, 4))
            );

            $period = ProductionPeriod::create([
                'coop_id' => $validated['coop_id'],
                'pic_id' => $validated['pic_id'],
                'period_code' => $periodCode,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'initial_stock' => $validated['initial_stock'],
                'closing_reason' => $validated['closing_reason'] ?? null,
                'status' => 'active',
                'sync_status' => 'SYNCED',
                'created_at_client' => $validated['created_at_client'],
                'created_at_server' => now(),
                'updated_at_client' => $validated['updated_at_client'] ?? $validated['created_at_client'],
                'updated_at_server' => now(),
            ]);

            $period->load(['coop:id,name', 'pic:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'Siklus produksi berhasil dibuat.',
                'data' => new ProductionPeriodResource($period),
            ], 201);
        });
    }
}
