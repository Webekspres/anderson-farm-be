<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncGetEducationRequest;
use App\Http\Resources\Api\V1\EducationArticleResource;
use App\Http\Resources\Api\V1\PriceReferenceResource;
use App\Services\Api\V1\Sync\EducationSyncService;
use Illuminate\Http\JsonResponse;

class EducationSyncController extends Controller
{
    protected EducationSyncService $service;

    public function __construct(EducationSyncService $service)
    {
        $this->service = $service;
    }

    public function index(SyncGetEducationRequest $request): JsonResponse
    {
        $lastSync = $request->input('last_sync_timestamp');
        $payload = $this->service->getDeltaPayload($lastSync);

        return response()->json([
            'success' => true,
            'current_server_timestamp' => now()->toIso8601String(),
            'data' => [
                'education_articles' => EducationArticleResource::collection($payload['education_articles']),
                'price_references' => PriceReferenceResource::collection($payload['price_references']),
            ],
        ]);
    }
}
