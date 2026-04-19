<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CoopDocument\StoreCoopDocumentRequest;
use App\Http\Resources\Api\V1\CoopDocumentResource;
use App\Models\Coop;
use App\Models\CoopDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CoopDocumentController extends Controller
{
    /**
     * Display a listing of the documents for a specific coop.
     */
    public function index(string $coop): JsonResponse
    {
        $coopModel = Coop::find($coop);
        if (!$coopModel) {
            return response()->json([
                'success' => false,
                'message' => 'Kandang tidak ditemukan',
                'data' => null,
            ], 404);
        }
        $documents = CoopDocument::where('coop_id', $coop)
            ->whereNull('deleted_at')
            ->get();
        return response()->json([
            'success' => true,
            'message' => 'Daftar dokumen berhasil dimuat',
            'data' => CoopDocumentResource::collection($documents),
        ]);
    }

    /**
     * Store a newly created document for a specific coop.
     */
    public function store(StoreCoopDocumentRequest $request, string $coop): JsonResponse
    {
        $coopModel = Coop::find($coop);
        if (!$coopModel) {
            return response()->json([
                'success' => false,
                'message' => 'Kandang tidak ditemukan',
                'data' => null,
            ], 404);
        }
        $document = CoopDocument::create([
            'coop_id' => $coop,
            'name' => $request->input('document_name'),
            'file_type' => $request->input('document_type'),
            'file_url' => $request->input('file_url'),
            'file_path_local' => null,
            'version' => 1,
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil ditambahkan',
            'data' => new CoopDocumentResource($document),
        ], 201);
    }

    /**
     * Soft delete a document for a specific coop.
     */
    public function destroy(string $coop, string $document): JsonResponse
    {
        $coopModel = Coop::find($coop);
        if (!$coopModel) {
            return response()->json([
                'success' => false,
                'message' => 'Kandang tidak ditemukan',
                'data' => null,
            ], 404);
        }
        $documentModel = CoopDocument::where('id', $document)
            ->where('coop_id', $coop)
            ->whereNull('deleted_at')
            ->first();
        if (!$documentModel) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen tidak ditemukan atau tidak milik kandang ini',
                'data' => null,
            ], 404);
        }
        $documentModel->deleted_at = now();
        $documentModel->save();
        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil dihapus',
            'data' => null,
        ]);
    }
}
