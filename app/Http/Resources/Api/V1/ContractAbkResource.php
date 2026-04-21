<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractAbkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period_id' => $this->period_id,
            'title' => $this->title,
            'file_url' => $this->file_url,
            'uploaded_by' => $this->uploaded_by,
            // Opsional: muat relasi jika diminta
            'uploader_name' => $this->whenLoaded('uploader', fn() => $this->uploader->name),
            'created_at' => $this->created_at_client?->toIso8601String(),
        ];
    }
}
