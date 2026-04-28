<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Sync;

use App\Http\Resources\Api\V1\Sync\ContractAbkSyncResource;
use App\Http\Resources\Api\V1\Sync\EmployeeSalarySyncResource;
use App\Http\Resources\Api\V1\Sync\PeriodDocumentSyncResource;
use App\Http\Resources\Api\V1\Sync\PeriodInvestorSyncResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodSyncResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'coop_id' => $this->coop_id,
            'period_code' => $this->period_code,
            'start_date' => optional($this->start_date)->toDateString(),
            'end_date' => optional($this->end_date)->toDateString(),
            'initial_stock' => $this->initial_stock,
            'status' => $this->status,
            'sync_status' => $this->sync_status,
            'created_at_client' => optional($this->created_at_client)->toIso8601String(),
            'created_at_server' => optional($this->created_at_server)->toIso8601String(),
            'updated_at_client' => optional($this->updated_at_client)->toIso8601String(),
            'updated_at_server' => optional($this->updated_at_server)->toIso8601String(),
            'deleted_at' => optional($this->deleted_at)->toIso8601String(),
            'investors' => PeriodInvestorSyncResource::collection($this->whenLoaded('investors')),
            'salaries' => EmployeeSalarySyncResource::collection($this->whenLoaded('salaries')),
            'contracts' => ContractAbkSyncResource::collection($this->whenLoaded('contracts')),
            'documents' => PeriodDocumentSyncResource::collection($this->whenLoaded('documents')),
        ];
    }
}
