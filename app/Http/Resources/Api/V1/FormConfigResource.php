<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class FormConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'version' => $this->version,
            'category' => $this->category,
            'key_name' => $this->key_name,
            'config_value' => $this->config_value,
            'created_at_client' => $this->created_at_client?->toIso8601String(),
            'created_at_server' => $this->created_at_server?->toIso8601String(),
            'updated_at_client' => $this->updated_at_client?->toIso8601String(),
            'updated_at_server' => $this->updated_at_server?->toIso8601String(),
            // Sinkronisasi/internal fields disembunyikan
        ];
    }
}
