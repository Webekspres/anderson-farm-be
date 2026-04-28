<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'version' => $this->version,
            'period_id' => $this->period_id,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'harvest_id' => $this->harvest_id,
            'salary_id' => $this->salary_id,
            'date' => $this->date?->toIso8601String(),
            'amount' => $this->amount,
            'description' => $this->description,
            'reference_no' => $this->reference_no,
            'receipt_url' => $this->receipt_url,
            'receipt_path_local' => $this->receipt_path_local,
            'business_status' => $this->business_status,
            'approved_by' => $this->approved_by,
            'rejection_reason' => $this->rejection_reason,
            'linked_transaction_id' => $this->linked_transaction_id,
            'sync_status' => $this->sync_status,
            'created_at_client' => $this->created_at_client?->toIso8601String(),
            'created_at_server' => $this->created_at_server?->toIso8601String(),
            'updated_at_client' => $this->updated_at_client?->toIso8601String(),
            'updated_at_server' => $this->updated_at_server?->toIso8601String(),
            'sync_metadata' => $this->sync_metadata ? json_decode($this->sync_metadata, true) : null,
        ];
    }
}
