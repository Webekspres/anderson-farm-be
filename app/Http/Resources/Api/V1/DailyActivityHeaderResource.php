<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyActivityHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'version' => $this->version,
            'form_schema_version' => $this->form_schema_version,
            'period_id' => $this->period_id,
            'user_id' => $this->user_id,
            'date' => $this->date?->toIso8601String(),
            'age_days' => $this->age_days,
            'mortality_count' => $this->mortality_count,
            'cull_count' => $this->cull_count,
            'feed_consumption_kg' => $this->feed_consumption_kg !== null ? (float) $this->feed_consumption_kg : 0,
            'average_weight' => $this->average_weight,
            'business_status' => $this->business_status,
            'approved_by' => $this->approved_by,
            'rejection_reason' => $this->rejection_reason,
            'sync_status' => $this->sync_status,
            'created_at_client' => $this->created_at_client?->toIso8601String(),
            'created_at_server' => $this->created_at_server?->toIso8601String(),
            'updated_at_client' => $this->updated_at_client?->toIso8601String(),
            'updated_at_server' => $this->updated_at_server?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'sync_metadata' => $this->sync_metadata,

            // Optional loaded relationships
            'dynamic_logs' => DailyDynamicLogResource::collection($this->whenLoaded('dynamicLogs')),
            'harvests' => HarvestEntryResource::collection($this->whenLoaded('harvests')),
            'ovk_usages' => OvkUsageResource::collection($this->whenLoaded('ovkUsages')),
            'photos' => PhotoEvidenceResource::collection($this->whenLoaded('photos')),
            'checklist_logs' => DailyChecklistLogResource::collection($this->whenLoaded('dailyChecklistLogs')),
        ];
    }
}
