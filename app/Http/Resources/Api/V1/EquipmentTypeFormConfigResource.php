<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentTypeFormConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'version' => $this->version,
            'equipment_type_id' => $this->equipment_type_id,
            'form_config_id' => $this->form_config_id,
            'display_order' => $this->display_order,
            'sync_status' => $this->sync_status,
            'created_at_client' => $this->created_at_client?->toIso8601String(),
            'created_at_server' => $this->created_at_server?->toIso8601String(),
            'updated_at_client' => $this->updated_at_client?->toIso8601String(),
            'updated_at_server' => $this->updated_at_server?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'equipment_type' => new EquipmentTypeResource($this->whenLoaded('equipmentType')),
            'form_config' => new FormConfigResource($this->whenLoaded('formConfig')),
        ];
    }
}
