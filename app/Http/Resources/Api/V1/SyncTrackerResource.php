<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncTrackerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'table_name' => $this->table_name,
            'last_server_id' => $this->last_server_id,
            'last_sync_at' => $this->last_sync_at?->toIso8601String(),
        ];
    }
}
