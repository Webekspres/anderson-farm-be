<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MonitoringKpiRequest;
use App\Services\MonitoringService;
use Illuminate\Http\JsonResponse;

class MonitoringController extends Controller
{
    public function __construct(
        public MonitoringService $monitoring,
    ) {}

    /**
     * GET /api/v1/monitoring/kpi?period_id=
     */
    public function kpi(MonitoringKpiRequest $request): JsonResponse
    {
        $data = $this->monitoring->computeKpi($request->validated('period_id'));

        return response()->json([
            'success' => true,
            'message' => 'KPI monitoring berhasil dihitung.',
            'data' => $data,
        ]);
    }

    /**
     * GET /api/v1/monitoring/deviations?period_id=
     */
    public function deviations(MonitoringKpiRequest $request): JsonResponse
    {
        $data = $this->monitoring->computeDeviations($request->validated('period_id'));

        return response()->json([
            'success' => true,
            'message' => 'Deviasi berhasil dihitung.',
            'data' => $data,
        ]);
    }
}
