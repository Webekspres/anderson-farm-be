<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Upload\DeleteFileRequest;
use App\Http\Requests\Api\V1\Upload\UploadFileRequest;
use App\Services\Api\ObjectStorageService;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    public function __construct(
        private readonly ObjectStorageService $objectStorage,
    ) {}

    /**
     * Handle file upload.
     */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $stored = $this->objectStorage->storeUploadedFile(
            $request->file('file'),
            $request->string('folder')->toString(),
        );

        return response()->json([
            'success' => true,
            'message' => 'File berhasil diunggah',
            'data' => [
                'image_url' => $stored['url'],
                'image_path_local' => $stored['path'],
            ],
        ], 201);
    }

    /**
     * Handle file deletion.
     */
    public function destroy(DeleteFileRequest $request): JsonResponse
    {
        $filePath = $request->string('file_path')->toString();

        if (! $this->objectStorage->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan',
                'data' => null,
            ], 404);
        }

        $this->objectStorage->delete($filePath);

        return response()->json([
            'success' => true,
            'message' => 'File berhasil dihapus',
            'data' => null,
        ], 200);
    }
}
