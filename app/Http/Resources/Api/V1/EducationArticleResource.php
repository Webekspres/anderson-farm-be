<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class EducationArticleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'name' => $this->name,
            'title' => $this->name, // alias untuk kompatibilitas
            'excerpt' => $this->excerpt,
            'content_html' => $this->content_html,
            'category' => $this->category,
            'author_name' => $this->author_name,
            'link_url' => $this->link_url,
            'image_url' => $this->image_url,
            'image_path_local' => $this->image_path_local,
            'created_at_server' => $this->created_at_server?->toIso8601String(),
            'updated_at_server' => $this->updated_at_server?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
