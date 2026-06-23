<?php

namespace App\Http\Requests\Api\V1\Farm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_id' => ['sometimes', 'uuid', 'exists:areas,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sync_status' => ['sometimes', 'in:LOCAL_SAVED,PENDING_SYNC,SYNCED,SYNC_FAILED,CONFLICT'],
            'created_at_client' => ['sometimes', 'date'],
            'created_at_server' => ['sometimes', 'date'],
            'updated_at_client' => ['sometimes', 'date'],
            'updated_at_server' => ['sometimes', 'date'],
            'sync_metadata' => ['sometimes', 'string'],
            'type' => ['sometimes', 'in:broiler,layer,breeder,other'],
        ];
    }
}
