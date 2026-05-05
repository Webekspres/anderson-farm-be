<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Contract\AcceptContractRequest;
use App\Http\Requests\Api\V1\Contract\StoreContractAbkRequest;
use App\Http\Resources\Api\V1\ContractAbkResource;
use App\Jobs\NotifyAbksOfNewContractJob;
use App\Models\ContractAbk;
use App\Models\ContractAcceptance;
use App\Models\ProductionPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ContractAbkController extends Controller
{
    /**
     * Melihat daftar kontrak yang tertaut dengan periode.
     */
    public function index(string $periodId): JsonResponse
    {
        $period = ProductionPeriod::findOrFail($periodId);

        $contracts = ContractAbk::with('uploader') // Eager load relasi uploader
            ->where('period_id', $period->id)
            ->orderBy('created_at_client', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar kontrak.',
            'data' => ContractAbkResource::collection($contracts),
        ], 200);
    }

    /**
     * Menyimpan data kontrak baru untuk periode tersebut.
     */
    public function store(StoreContractAbkRequest $request, string $periodId): JsonResponse
    {
        $period = ProductionPeriod::findOrFail($periodId);
        $validated = $request->validated();

        $contract = ContractAbk::create([
            'id' => Str::uuid()->toString(),
            'period_id' => $period->id,
            'title' => $validated['title'],
            'file_url' => $validated['file_url'] ?? null,
            'file_path_local' => $validated['file_path_local'] ?? null,
            'uploaded_by' => Auth::id(), // ID manajer yang sedang login
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ]);

        // Muat relasi uploader untuk format response
        $contract->load('uploader');

        NotifyAbksOfNewContractJob::dispatch($period->id)->afterResponse();

        return response()->json([
            'success' => true,
            'message' => 'Kontrak berhasil ditambahkan.',
            'data' => new ContractAbkResource($contract),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $contract = ContractAbk::with(['uploader', 'acceptances.user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail kontrak ditemukan.',
            'data' => new ContractAbkResource($contract),
        ]);
    }

    public function accept(AcceptContractRequest $request, string $id): JsonResponse
    {
        $contract = ContractAbk::findOrFail($id);
        $userId = Auth::id();

        // 1. Cek apakah user sudah pernah menyetujui kontrak ini
        $alreadyAccepted = $contract->acceptances()
            ->where('user_id', '=', $userId)
            ->exists();

        if ($alreadyAccepted) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah menyetujui kontrak ini sebelumnya.',
                'data' => null,
            ], 422);
        }

        // 2. Simpan persetujuan
        $acceptance = ContractAcceptance::create([
            'id' => Str::uuid()->toString(),
            'contract_id' => $contract->id,
            'user_id' => $userId,
            'accepted_at' => now(),
            'device_id' => $request->device_id,
            'sync_status' => 'SYNCED',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kontrak berhasil disetujui secara digital.',
            'data' => $acceptance,
        ], 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $contract = ContractAbk::findOrFail($id);

        // Proteksi: Jika sudah ada yang tanda tangan, kontrak tidak boleh dihapus
        if ($contract->acceptances()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kontrak tidak bisa dihapus karena sudah ada yang menyetujui.',
                'data' => null,
            ], 403);
        }

        $contract->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Kontrak berhasil dihapus.',
            'data' => null,
        ]);
    }
}
