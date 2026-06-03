<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionPeriodResource extends JsonResource
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
            'period_code' => $this->period_code,
            'status' => $this->status,
            'floor' => $this->whenLoaded('floor', fn () => [
                'id' => $this->floor->id,
                'name' => $this->floor->name,
            ]),
            'pic' => $this->whenLoaded('pic', fn () => [
                'id' => $this->pic->id,
                'name' => $this->pic->name,
            ]),
            'start_date' => optional($this->start_date)->toDateString(),
            'end_date' => optional($this->end_date)->toDateString(),
            'initial_stock' => $this->initial_stock,
            'closing_reason' => $this->closing_reason,
            'closed_at' => optional($this->closed_at)->toIso8601String(),
            'sync_status' => $this->sync_status,
            'created_at_client' => optional($this->created_at_client)->toIso8601String(),
            'created_at_server' => optional($this->created_at_server)->toIso8601String(),
            'updated_at_client' => optional($this->updated_at_client)->toIso8601String(),
            'updated_at_server' => optional($this->updated_at_server)->toIso8601String(),
            'deleted_at' => optional($this->deleted_at)->toIso8601String(),
            'contracts' => ContractAbkResource::collection($this->whenLoaded('contracts')),
        ];
    }
}
