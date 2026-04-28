<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoopFloorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'coop_id'   => $this->coop_id,
            'name'      => $this->name,
            'capacity'  => $this->capacity,
            'coop_type' => $this->coop_type,
        ];
    }
}
