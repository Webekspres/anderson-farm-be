<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyChecklistLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'header_id' => $this->header_id,
            'task_id' => $this->task_id,
            'boolean_value' => $this->boolean_value,
            'text_value' => $this->text_value,
            'notes' => $this->notes,
            'sync_status' => $this->sync_status,
            'created_at_client' => $this->created_at_client?->toIso8601String(),
            'updated_at_client' => $this->updated_at_client?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
