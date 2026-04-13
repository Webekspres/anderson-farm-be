<?php

namespace App\Http\Resources\Api\V1;

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
        return [
            'id'           => $this->id,
            'server_id'    => $this->server_id,
            'username'     => $this->username,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone_number' => $this->phone_number,
            'role'         => $this->role,
            'is_active'    => $this->is_active,
            'sync_status'  => $this->sync_status,
            'created_at'   => $this->created_at_server ? clone $this->created_at_server : null,
        ];
    }
}
