<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CoopDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'version' => $this->version,
            'floor_id' => $this->floor_id,
            'name' => $this->name,
            'file_path_local' => $this->file_path_local,
            'file_url' => $this->file_url,
            'file_type' => $this->file_type,
            'sync_status' => $this->sync_status,
            'created_at_client' => optional($this->created_at_client)?->toIso8601String(),
            'created_at_server' => optional($this->created_at_server)?->toIso8601String(),
            'updated_at_client' => optional($this->updated_at_client)?->toIso8601String(),
            'updated_at_server' => optional($this->updated_at_server)?->toIso8601String(),
            'deleted_at' => optional($this->deleted_at)?->toIso8601String(),
        ];
    }
}
