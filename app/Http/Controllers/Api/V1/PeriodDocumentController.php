<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Period\StorePeriodDocumentRequest;
use App\Http\Resources\Api\V1\PeriodDocumentResource;
use App\Models\PeriodDocument;
use App\Models\ProductionPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PeriodDocumentController extends Controller
{
    public function index(string $periodId): JsonResponse
    {
        $period = ProductionPeriod::findOrFail($periodId);

        $documents = PeriodDocument::with('uploader')
            ->where('period_id', $period->id)
            ->orderBy('created_at_client', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar dokumen.',
            'data'    => PeriodDocumentResource::collection($documents),
        ], 200);
    }

    public function store(StorePeriodDocumentRequest $request, string $periodId): JsonResponse
    {
        $period = ProductionPeriod::findOrFail($periodId);
        $validated = $request->validated();

        $document = PeriodDocument::create([
            'id'                => Str::uuid()->toString(),
            'period_id'         => $period->id,
            'title'             => $validated['title'],
            'document_type'     => $validated['document_type'],
            'file_url'          => $validated['file_url'] ?? null,
            'file_path_local'   => $validated['file_path_local'] ?? null,
            'uploaded_by'       => Auth::id(), // ID user yang login (PIC/ABK)
            'sync_status'       => 'PENDING_SYNC',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ]);

        $document->load('uploader');

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diunggah.',
            'data'    => new PeriodDocumentResource($document),
        ], 201);
    }
}
