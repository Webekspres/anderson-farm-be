<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoopFloorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->server_id !== null ? (int) $this->server_id : null,
            'uuid' => $this->id,
            'coop_id' => $this->coop_id,
            'name' => $this->name,
            'capacity' => $this->capacity !== null ? (int) $this->capacity : null,
            'sync_status' => $this->sync_status,
            'created_at_client' => $this->created_at_client?->toIso8601String(),
            'created_at_server' => $this->created_at_server?->toIso8601String(),
            'updated_at_client' => $this->updated_at_client?->toIso8601String(),
            'updated_at_server' => $this->updated_at_server?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'coop' => $this->whenLoaded('coop', fn () => [
                'id' => $this->coop->server_id !== null ? (int) $this->coop->server_id : null,
                'uuid' => $this->coop->id,
                'name' => $this->coop->name,
                'coop_type' => $this->coop->coop_type,
            ]),
        ];
    }
}
