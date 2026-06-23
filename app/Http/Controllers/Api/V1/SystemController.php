<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\System\CheckVersionRequest;
use App\Http\Resources\Api\V1\System\CheckVersionResource;
use App\Services\Api\V1\System\AppVersionService;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    public function __construct(
        private readonly AppVersionService $appVersionService,
    ) {}

    public function checkVersion(CheckVersionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->appVersionService->evaluate(
            $validated['platform'],
            $validated['current_version'],
        );

        return (new CheckVersionResource($result))->additional([
            'success' => true,
            'message' => 'Pengecekan versi berhasil.',
        ])->response();
    }
}
