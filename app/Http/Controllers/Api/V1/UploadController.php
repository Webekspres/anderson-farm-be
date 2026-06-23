<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Upload\UploadFileRequest;
use App\Http\Requests\Api\V1\Upload\DeleteFileRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    /**
     * Handle file upload.
     */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $folder = $request->input('folder');
        $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, 'public');

        return response()->json([
            'success' => true,
            'message' => 'File berhasil diunggah',
            'data' => [
                'image_url' => asset(Storage::url($path)),
                'image_path_local' => $path,
            ],
        ], 201);
    }

    /**
     * Handle file deletion.
     */
    public function destroy(DeleteFileRequest $request): JsonResponse
    {
        $filePath = $request->input('file_path');
        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan',
                'data' => null,
            ], 404);
        }

        Storage::disk('public')->delete($filePath);

        return response()->json([
            'success' => true,
            'message' => 'File berhasil dihapus',
            'data' => null,
        ], 200);
    }
}
