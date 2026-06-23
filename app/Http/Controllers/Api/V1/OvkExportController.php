<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ExportOvkUsagesRequest;
use App\Models\ProductionPeriod;
use App\Services\OvkExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OvkExportController extends Controller
{
    public function __construct(private OvkExportService $exportService) {}

    public function show(ExportOvkUsagesRequest $request): StreamedResponse
    {
        $period = ProductionPeriod::query()->findOrFail($request->validated('period_id'));

        return $this->exportService->export($period);
    }
}
