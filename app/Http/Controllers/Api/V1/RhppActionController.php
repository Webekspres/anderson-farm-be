<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rhpp\PublishRhppRequest;
use App\Services\Api\RhppActionService;
use Illuminate\Http\JsonResponse;

class RhppActionController extends Controller
{
    public function __construct(
        private readonly RhppActionService $rhppActionService,
    ) {}

    public function publish(PublishRhppRequest $request, string $period_id): JsonResponse
    {
        $result = $this->rhppActionService->publishRhpp(
            periodId: $period_id,
            syncTimestamp: $request->validated('sync_timestamp')
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan RHPP resmi dipublikasi.',
            'data'    => $result,
        ]);
    }
}
