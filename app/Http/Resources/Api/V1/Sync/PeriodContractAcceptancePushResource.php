<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Sync;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response payload for POST /api/v1/sync/periods (contract acceptance push).
 *
 * @property-read array<int, array{id: string, status: string}> $resource
 */
class PeriodContractAcceptancePushResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sync_results' => collect($this->resource)->map(static fn (array $row): array => [
                'id' => $row['id'],
                'status' => $row['status'],
            ])->values()->all(),
        ];
    }
}
