<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PriceReferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'version' => $this->version,
            'name' => $this->name,
            'highlight_price' => $this->highlight_price,
            'link_url' => $this->link_url,
            'image_url' => $this->image_url,
            'image_path_local' => $this->image_path_local,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
