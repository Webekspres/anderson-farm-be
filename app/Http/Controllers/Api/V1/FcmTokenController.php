<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\UpdateFcmTokenRequest;
use Illuminate\Http\JsonResponse;

class FcmTokenController extends Controller
{
    public function __invoke(UpdateFcmTokenRequest $request): JsonResponse
    {
        $request->user()->update([
            'fcm_token' => $request->validated('fcm_token'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token berhasil diperbarui.',
            'data' => null,
        ]);
    }
}
