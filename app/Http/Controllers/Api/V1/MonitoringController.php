<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AcknowledgeDeviationRequest;
use App\Http\Requests\Api\V1\MonitoringKpiRequest;
use App\Http\Resources\Api\V1\MonitoringDeviationAckResource;
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
        $periodId = $request->validated('period_id');
        $deviations = $this->monitoring->computeDeviations($periodId);
        $userId = (string) $request->user()->id;
        $data = $this->monitoring->attachAcknowledgements($deviations, $periodId, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Deviasi berhasil dihitung.',
            'data' => $data,
        ]);
    }

    /**
     * POST /api/v1/monitoring/deviations/acknowledge
     */
    public function acknowledge(AcknowledgeDeviationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $ack = $this->monitoring->acknowledgeDeviation(
            $validated['period_id'],
            (string) $request->user()->id,
            $validated['metric'],
            $validated['date'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Deviasi ditandai sudah ditinjau.',
            'data' => new MonitoringDeviationAckResource($ack),
        ]);
    }
}
