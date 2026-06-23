<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ExportSalaryTemplateRequest;
use App\Services\SalaryTemplateExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalaryExportController extends Controller
{
    public function __construct(
        private readonly SalaryTemplateExportService $exportService,
    ) {}

    public function show(ExportSalaryTemplateRequest $request): StreamedResponse
    {
        return $this->exportService->export();
    }
}
