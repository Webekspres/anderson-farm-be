<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'version' => $this->version,
            'name' => $this->name,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'sync_status' => $this->sync_status,
            'created_at_client' => optional($this->created_at_client)->toIso8601String(),
            'created_at_server' => optional($this->created_at_server)->toIso8601String(),
            'updated_at_client' => optional($this->updated_at_client)->toIso8601String(),
            'updated_at_server' => optional($this->updated_at_server)->toIso8601String(),
            'deleted_at' => optional($this->deleted_at)->toIso8601String(),
        ];
    }
}
