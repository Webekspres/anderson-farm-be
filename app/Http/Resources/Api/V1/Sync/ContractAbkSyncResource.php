<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Sync;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractAbkSyncResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period_id' => $this->period_id,
            'title' => $this->title,
            'file_url' => $this->file_url,
            'uploaded_by' => $this->uploaded_by,
            'sync_status' => $this->sync_status,
            'created_at_client' => optional($this->created_at_client)->toIso8601String(),
            'created_at_server' => optional($this->created_at_server)->toIso8601String(),
            'updated_at_client' => optional($this->updated_at_client)->toIso8601String(),
            'updated_at_server' => optional($this->updated_at_server)->toIso8601String(),
            'deleted_at' => optional($this->deleted_at)->toIso8601String(),
            'acceptances' => ContractAcceptanceSyncResource::collection($this->whenLoaded('acceptances')),
        ];
    }
}
