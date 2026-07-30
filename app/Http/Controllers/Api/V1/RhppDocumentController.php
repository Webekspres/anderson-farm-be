<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rhpp\GenerateRhppRequest;
use App\Http\Requests\Api\V1\Rhpp\UploadRhppDocumentRequest;
use App\Services\Api\RhppDocumentService;
use Illuminate\Http\JsonResponse;

class RhppDocumentController extends Controller
{
    public function __construct(
        private readonly RhppDocumentService $rhppDocumentService,
    ) {}

    /**
     * POST /api/v1/periods/{period_id}/rhpp/generate
     *
     * Hitung totals dari data operasional dan simpan draft RHPP.
     */
    public function generate(GenerateRhppRequest $request, string $period_id): JsonResponse
    {
        $result = $this->rhppDocumentService->generateDraft($period_id);
        $rhpp = $result['rhpp'];
        $metrics = $result['metrics'];

        return response()->json([
            'success' => true,
            'message' => 'Draft RHPP berhasil dihitung dari data operasional.',
            'data' => [
                'rhpp_id' => $rhpp->id,
                'period_id' => $rhpp->period_id,
                'publish_status' => $rhpp->publish_status,
                'total_income' => $rhpp->total_income,
                'total_expense' => $rhpp->total_expense,
                'net_profit' => $rhpp->net_profit,
                'metrics' => $metrics,
            ],
        ]);
    }

    /**
     * POST /api/v1/periods/{period_id}/rhpp-documents
     *
     * Unggah dokumen RHPP PDF beserta angka finansial final ke server.
     * Sistem otomatis membuat atau memperbarui shell Rhpp untuk periode ini.
     */
    public function store(UploadRhppDocumentRequest $request, string $period_id): JsonResponse
    {
        $result = $this->rhppDocumentService->uploadDocument(
            periodId: $period_id,
            validatedData: $request->validated(),
            file: $request->file('document'),
        );

        $rhpp = $result['rhpp'];
        $document = $result['document'];

        return response()->json([
            'success' => true,
            'message' => 'Dokumen RHPP berhasil diunggah dan disimpan.',
            'data' => [
                'rhpp_id' => $rhpp->id,
                'document_id' => $document->id,
                'file_url' => $document->file_url,
                'publish_status' => $rhpp->publish_status,
            ],
        ]);
    }
}
