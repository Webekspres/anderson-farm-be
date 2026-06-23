<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ImportSalaryRequest;
use App\Services\SalaryImportService;
use Illuminate\Http\JsonResponse;

class SalaryImportController extends Controller
{
    public function __construct(
        private readonly SalaryImportService $importService,
    ) {}

    public function store(ImportSalaryRequest $request): JsonResponse
    {
        $result = $this->importService->import(
            $request->file('file'),
            $request->user(),
        );

        return response()->json([
            'success' => $result->success,
            'message' => $result->message,
            'data' => $result->data,
        ], $result->statusCode);
    }
}
