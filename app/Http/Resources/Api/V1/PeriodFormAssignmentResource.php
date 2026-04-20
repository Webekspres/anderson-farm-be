<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PeriodFormAssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'period_id' => $this->period_id,
            'form_config_id' => $this->form_config_id,
            'display_order' => $this->display_order,
            'is_active' => $this->is_active,
            'sync_status' => $this->sync_status,
            'created_at_client' => optional($this->created_at_client)?->toIso8601String(),
            'created_at_server' => optional($this->created_at_server)?->toIso8601String(),
            'updated_at_client' => optional($this->updated_at_client)?->toIso8601String(),
            'updated_at_server' => optional($this->updated_at_server)?->toIso8601String(),
            'deleted_at' => optional($this->deleted_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
