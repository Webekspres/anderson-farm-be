<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringDeviationAckResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'period_id' => $this->period_id,
            'metric' => $this->metric,
            'deviation_date' => $this->deviation_date?->toDateString(),
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
        ];
    }
}
