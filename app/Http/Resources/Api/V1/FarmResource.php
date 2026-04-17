<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->server_id,
            'uuid'                => $this->id,
            'area_id'             => $this->area_id,
            'name'                => $this->name,
            'address'             => $this->address,
            'is_active'           => $this->is_active,
            'sync_status'         => $this->sync_status,
            'created_at_client'   => $this->created_at_client?->toIso8601String(),
            'created_at_server'   => $this->created_at_server?->toIso8601String(),
            'updated_at_client'   => $this->updated_at_client?->toIso8601String(),
            'updated_at_server'   => $this->updated_at_server?->toIso8601String(),
            'deleted_at'          => $this->deleted_at?->toIso8601String(),
        ];
    }
}
