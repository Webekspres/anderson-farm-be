<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Memilih dan memformat data apa saja yang boleh dilihat oleh Frontend/Mobile
        return [
            'id'          => $this->id,
            'full_name'   => $this->full_name,
            'username'    => $this->username,
            'role'        => $this->role,
            // Perhatikan: Kita TIDAK mengirimkan password_hash!
            'joined_at'   => $this->created_at_server?->format('Y-m-d H:i:s'),
        ];
    }
}
