<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Sync;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractAcceptanceSyncResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'user_id' => $this->user_id,
            'accepted_at' => optional($this->accepted_at)->toIso8601String(),
            'device_id' => $this->device_id,
            'current_loan_accumulated' => $this->current_loan_accumulated !== null ? (float) $this->current_loan_accumulated : 0.0,
            'remaining_loan_limit' => $this->remaining_loan_limit !== null ? (float) $this->remaining_loan_limit : 3000000.0,
            'sync_status' => $this->sync_status,
            'created_at_client' => optional($this->created_at_client)->toIso8601String(),
            'created_at_server' => optional($this->created_at_server)->toIso8601String(),
            'updated_at_client' => optional($this->updated_at_client)->toIso8601String(),
            'updated_at_server' => optional($this->updated_at_server)->toIso8601String(),
            'deleted_at' => optional($this->deleted_at)->toIso8601String(),
        ];
    }
}
