<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ExportBopDetailsRequest;
use App\Models\ProductionPeriod;
use App\Services\BopExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BopExportController extends Controller
{
    public function __construct(private BopExportService $exportService) {}

    public function show(ExportBopDetailsRequest $request): StreamedResponse
    {
        $period = ProductionPeriod::query()->findOrFail($request->validated('period_id'));

        return $this->exportService->export($period);
    }
}
