<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractAcceptanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'user_id' => $this->user_id,
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'device_id' => $this->device_id,
            'sync_status' => $this->sync_status,
        ];
    }
}
