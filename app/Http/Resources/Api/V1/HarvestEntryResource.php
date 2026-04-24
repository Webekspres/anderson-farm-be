<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HarvestEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'header_id' => $this->header_id,
            'rit_number' => $this->rit_number,
            'buyer_name' => $this->buyer_name,
            'total_birds' => $this->total_birds,
            'total_weight' => $this->total_weight,
            'price_per_kg' => $this->price_per_kg,
            'total_revenue' => $this->total_revenue,
            'delivery_order_no' => $this->delivery_order_no,
            'sync_status' => $this->sync_status,
            'created_at_client' => $this->created_at_client?->toIso8601String(),
            'created_at_server' => $this->created_at_server?->toIso8601String(),
            'updated_at_client' => $this->updated_at_client?->toIso8601String(),
            'updated_at_server' => $this->updated_at_server?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
