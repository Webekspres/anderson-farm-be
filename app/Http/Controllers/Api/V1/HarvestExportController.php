<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ExportHarvestRequest;
use App\Models\ProductionPeriod;
use App\Services\HarvestExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HarvestExportController extends Controller
{
    public function __construct(private HarvestExportService $exportService) {}

    public function show(ExportHarvestRequest $request): StreamedResponse
    {
        $period = ProductionPeriod::query()->findOrFail($request->validated('period_id'));

        return $this->exportService->export($period);
    }
}
