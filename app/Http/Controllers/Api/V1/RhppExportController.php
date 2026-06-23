<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ExportRhppRequest;
use App\Models\ProductionPeriod;
use App\Services\RhppExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RhppExportController extends Controller
{
    public function __construct(private RhppExportService $exportService) {}

    /**
     * Export RHPP data for a production period.
     *
     * @return StreamedResponse|JsonResponse
     */
    public function show(ExportRhppRequest $request)
    {
        $user = $request->user();

        // Role-based access control: Deny ABK role
        if ($user->role === 'abk') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya manager, PIC, admin, dan finance yang dapat mengexport RHPP.',
                'data' => null,
            ], 403);
        }

        // Get validated data
        $periodId = $request->validated('period_id');
        $format = $request->validated('format');

        // Fetch period
        $period = ProductionPeriod::find($periodId);
        if (! $period) {
            return response()->json([
                'success' => false,
                'message' => 'Periode tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        // Export and stream
        return $this->exportService->export($period, $format);
    }
}
