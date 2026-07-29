<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PeriodInvestorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'version' => $this->version,
            'period_id' => $this->period_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'profit_share_percentage' => $this->profit_share_percentage,
            'initial_investment' => $this->initial_investment,
            'final_dividend_amount' => $this->final_dividend_amount,
            'is_paid' => $this->is_paid,
            'sync_status' => $this->sync_status,
            'created_at_client' => $this->created_at_client?->toIso8601String(),
            'created_at_server' => $this->created_at_server?->toIso8601String(),
            'updated_at_client' => $this->updated_at_client?->toIso8601String(),
            'updated_at_server' => $this->updated_at_server?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
