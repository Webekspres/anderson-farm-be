<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * GET /api/v1/notifications
     * Mengambil daftar riwayat notifikasi milik user yang sedang login.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();

        // Ambil data notifikasi dengan paginasi (20 per halaman)
        $notifications = Notification::query()
            ->where('user_id', '=', $userId)
            ->orderBy('created_at_server', 'desc')
            ->paginate(20);

        // Hitung total notifikasi yang belum dibaca (untuk indikator titik merah/badge)
        $unreadCount = Notification::query()
            ->where('user_id', '=', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Data notifikasi berhasil diambil.',
            'data' => $notifications->items(),
            'meta' => [
                'unread_count' => $unreadCount,
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total_items' => $notifications->total(),
            ],
        ], 200);
    }

    /**
     * PATCH /api/v1/notifications/{id}/read
     * Menandai SATU notifikasi spesifik menjadi sudah dibaca.
     */
    public function markAsRead(string $id): JsonResponse
    {
        $notification = Notification::query()
            ->where('user_id', '=', Auth::id())
            ->where('id', '=', $id)
            ->firstOrFail(); // Akan otomatis return 404 jika ID tidak ditemukan atau bukan milik user ini

        if (is_null($notification->read_at)) {
            $notification->update([
                'read_at' => now(),
                'updated_at_server' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca.',
            'data' => $notification,
        ], 200);
    }

    /**
     * PATCH /api/v1/notifications/read-all
     * Menandai SEMUA notifikasi milik user ini menjadi sudah dibaca.
     */
    public function markAllAsRead(): JsonResponse
    {
        $now = now();

        Notification::query()
            ->where('user_id', '=', Auth::id())
            ->whereNull('read_at')
            ->update([
                'read_at' => $now,
                'updated_at_server' => $now,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
            'data' => null,
        ], 200);
    }
}
