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
            'id'        => $this->server_id,
            'username'  => $this->username,
            'name'      => $this->name,
            'role'      => $this->role,
            'email'     => $this->email,
            'phone'     => $this->phone_number, // mapping ke field phone_number di DB
            'is_active' => $this->is_active,
        ];
    }
}
