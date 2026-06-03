<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period_id' => $this->period_id,
            'task_name' => $this->task_name,
            'task_type' => $this->task_type,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
