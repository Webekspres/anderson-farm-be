<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'version' => $this->version,
            'floor_id' => $this->floor_id,
            'reported_by' => $this->reported_by,
            'date' => optional($this->date)?->toIso8601String(),
            'description' => $this->description,
            'image_path_local' => $this->image_path_local,
            'image_url' => $this->image_url,
            'status' => $this->status,
            'sync_status' => $this->sync_status,
            'created_at_client' => optional($this->created_at_client)?->toIso8601String(),
            'created_at_server' => optional($this->created_at_server)?->toIso8601String(),
            'updated_at_client' => optional($this->updated_at_client)?->toIso8601String(),
            'updated_at_server' => optional($this->updated_at_server)?->toIso8601String(),
            'deleted_at' => optional($this->deleted_at)?->toIso8601String(),
        ];
    }
}
