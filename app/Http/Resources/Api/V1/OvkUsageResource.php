<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OvkUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'header_id' => $this->header_id,
            'ovk_item_id' => $this->ovk_item_id,
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'sync_status' => $this->sync_status,
            'created_at_client' => $this->created_at_client?->toIso8601String(),
            'created_at_server' => $this->created_at_server?->toIso8601String(),
            'updated_at_client' => $this->updated_at_client?->toIso8601String(),
            'updated_at_server' => $this->updated_at_server?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
