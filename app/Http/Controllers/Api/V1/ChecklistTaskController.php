<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncChecklistTaskRequest;
use App\Http\Resources\Api\V1\ChecklistTaskResource;
use App\Models\ChecklistTask;
use App\Models\ProductionPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChecklistTaskController extends Controller
{
    /**
     * Melihat daftar tugas (SOP) untuk periode tertentu.
     */
    public function index(string $periodId): JsonResponse
    {
        // Validasi keberadaan periode
        $period = ProductionPeriod::findOrFail($periodId);

        $tasks = ChecklistTask::where('period_id', $period->id)
            ->orderBy('created_at_client', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar tugas SOP.',
            'data'    => ChecklistTaskResource::collection($tasks),
        ]);
    }

    /**
     * Sinkronisasi massal (Sapu Bersih & Tulis Ulang) daftar tugas.
     */
    public function sync(SyncChecklistTaskRequest $request, string $periodId): JsonResponse
    {
        // 1. Validasi periode ada di database
        $period = ProductionPeriod::findOrFail($periodId);

        $tasksData = $request->validated('tasks');
        $now = now();

        // 2. Format data baru sebelum di-insert
        $insertPayload = collect($tasksData)->map(function (array $task) use ($period, $now) {
            return [
                'id'                => Str::uuid()->toString(),
                'period_id'         => $period->id,
                'task_name'         => $task['task_name'],
                'task_type'         => $task['task_type'],
                'description'       => $task['description'] ?? null,
                'is_active'         => $task['is_active'] ?? true,
                'sync_status'       => 'PENDING_SYNC', // Menunggu ditarik oleh Mobile
                'created_at_client' => $now,
                'updated_at_client' => $now,
            ];
        })->toArray();

        // 3. Eksekusi Database Transaction (Sapu bersih & Tulis ulang)
        DB::transaction(function () use ($period, $insertPayload) {
            // Hapus permanen semua tugas lama untuk periode ini
            ChecklistTask::where('period_id', $period->id)->forceDelete();

            // Masukkan data baru jika array tidak kosong
            if (!empty($insertPayload)) {
                ChecklistTask::insert($insertPayload);
            }
        });

        // 4. Ambil kembali data yang sudah di-insert untuk response
        $updatedTasks = ChecklistTask::where('period_id', $period->id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar tugas SOP berhasil disinkronisasi.',
            'data'    => ChecklistTaskResource::collection($updatedTasks),
        ], 200);
    }
}
