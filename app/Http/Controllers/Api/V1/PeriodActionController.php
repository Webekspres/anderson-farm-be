<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Period\ClosePeriodRequest;
use App\Http\Resources\Api\V1\ProductionPeriodResource;
use App\Models\User;
use App\Services\Api\PeriodActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PeriodActionController extends Controller
{
    public function __construct(
        private readonly PeriodActionService $periodActionService,
    ) {}

    /**
     * POST /api/v1/periods/{id}/close
     *
     * Menutup periode produksi setelah melewati serangkaian validasi ketat:
     * RBAC → assignment → state → pre-flight data integrity.
     */
    public function close(ClosePeriodRequest $request, string $period_id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Semua logika bisnis dan validasi ada di Service layer
        $period = $this->periodActionService->closePeriod(
            periodId: $period_id,
            data: $request->validated(),
            user: $user,
        );

        $period->load(['floor:id,name', 'pic:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Periode berhasil ditutup.',
            'data'    => new ProductionPeriodResource($period),
        ]);
    }
}
