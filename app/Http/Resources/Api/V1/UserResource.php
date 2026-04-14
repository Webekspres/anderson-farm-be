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
            'id'                => $this->id,
            'server_id'         => $this->server_id,
            'version'           => $this->version,
            'username'          => $this->username,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone_number'      => $this->phone_number,
            'role'              => $this->role,
            'device_id'         => $this->device_id,
            'device_bound_at'   => $this->device_bound_at,
            'is_active'         => $this->is_active,
            'last_validated_at' => $this->last_validated_at,
            'fcm_token'         => $this->fcm_token,
            'sync_status'       => $this->sync_status,
            'created_at_client' => $this->created_at_client,
            'created_at_server' => $this->created_at_server,
            'updated_at_client' => $this->updated_at_client,
            'updated_at_server' => $this->updated_at_server,
            'deleted_at'        => $this->deleted_at,
            'sync_metadata'     => $this->sync_metadata,
        ];
    }
}
