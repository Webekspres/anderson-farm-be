<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Period\StorePeriodRequest;
use App\Http\Requests\Api\V1\Period\UpdatePeriodRequest;
use App\Http\Resources\Api\V1\ProductionPeriodResource;
use App\Models\ProductionPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeriodController extends Controller
{
    public function store(StorePeriodRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            // Unique code: YYYYMM + short floor hint + random (avoids collision for same-month creates).
            $periodCode = $validated['period_code'] ?? $this->generatePeriodCode($validated['floor_id']);

            $period = ProductionPeriod::create([
                'floor_id' => $validated['floor_id'],
                'pic_id' => $validated['pic_id'],
                'period_code' => $periodCode,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'initial_stock' => $validated['initial_stock'],
                'closing_reason' => $validated['closing_reason'] ?? null,
                'status' => 'draft',
                'sync_status' => 'SYNCED',
                'created_at_client' => $validated['created_at_client'],
                'created_at_server' => now(),
                'updated_at_client' => $validated['updated_at_client'] ?? $validated['created_at_client'],
                'updated_at_server' => now(),
            ]);

            $period->load(['floor:id,name', 'pic:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'Siklus produksi berhasil dibuat.',
                'data' => new ProductionPeriodResource($period),
            ], 201);
        });
    }

    public function update(UpdatePeriodRequest $request, string $id): JsonResponse
    {
        $period = ProductionPeriod::findOrFail($id);
        $validated = $request->validated();

        // Logika Tambahan: Cek ketersediaan kandang jika floor_id diubah
        if (isset($validated['floor_id']) && $validated['floor_id'] !== $period->floor_id) {
            $isBusy = ProductionPeriod::where('floor_id', $validated['floor_id'])
                ->where('status', 'active')
                ->where('id', '!=', $id)
                ->exists();

            if ($isBusy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kandang tujuan sedang digunakan oleh periode aktif lain.',
                ], 422);
            }
        }

        // Update data (hanya yang dikirim di request)
        $period->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Periode ternak berhasil diperbarui.',
            'data' => new ProductionPeriodResource($period),
        ], 200);
    }

    private function generatePeriodCode(string $floorId): string
    {
        $prefix = 'PRD-'.now()->format('Ym').'-'.strtoupper(substr($floorId, 0, 4));

        do {
            $periodCode = $prefix.'-'.strtoupper(Str::random(4));
        } while (ProductionPeriod::withTrashed()->where('period_code', $periodCode)->exists());

        return $periodCode;
    }
}
