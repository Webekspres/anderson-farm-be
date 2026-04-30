<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rhpp\UploadRhppDocumentRequest;
use App\Services\Api\RhppDocumentService;
use Illuminate\Http\JsonResponse;

class RhppDocumentController extends Controller
{
    public function __construct(
        private readonly RhppDocumentService $rhppDocumentService,
    ) {}

    /**
     * POST /api/v1/periods/{period_id}/rhpp-documents
     *
     * Unggah dokumen RHPP PDF beserta angka finansial final ke server.
     * Sistem otomatis membuat atau memperbarui shell Rhpp untuk periode ini.
     */
    public function store(UploadRhppDocumentRequest $request, string $period_id): JsonResponse
    {
        $result = $this->rhppDocumentService->uploadDocument(
            periodId:      $period_id,
            validatedData: $request->validated(),
            file:          $request->file('document'),
        );

        $rhpp     = $result['rhpp'];
        $document = $result['document'];

        return response()->json([
            'success' => true,
            'message' => 'Dokumen RHPP berhasil diunggah dan disimpan.',
            'data'    => [
                'rhpp_id'        => $rhpp->id,
                'document_id'    => $document->id,
                'file_url'       => $document->file_url,
                'publish_status' => $rhpp->publish_status,
            ],
        ]);
    }
}
