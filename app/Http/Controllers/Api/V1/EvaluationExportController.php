<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ExportEvaluationRequest;
use App\Models\ProductionPeriod;
use App\Services\EvaluationExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvaluationExportController extends Controller
{
    public function __construct(private EvaluationExportService $exportService) {}

    public function show(ExportEvaluationRequest $request): StreamedResponse
    {
        $period = ProductionPeriod::query()->findOrFail($request->validated('period_id'));

        return $this->exportService->export($period);
    }
}
