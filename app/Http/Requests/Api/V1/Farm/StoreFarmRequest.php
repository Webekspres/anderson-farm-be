<?php

namespace App\Http\Requests\Api\V1\Farm;

use Illuminate\Foundation\Http\FormRequest;

class StoreFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', 'uuid', 'exists:areas,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sync_status' => ['nullable', 'in:LOCAL_SAVED,PENDING_SYNC,SYNCED,SYNC_FAILED,CONFLICT'],
            'created_at_client' => ['nullable', 'date'],
            'created_at_server' => ['nullable', 'date'],
            'updated_at_client' => ['nullable', 'date'],
            'updated_at_server' => ['nullable', 'date'],
            'sync_metadata' => ['nullable', 'string'],
            'type' => ['required', 'in:broiler,layer,breeder,other'],
        ];
    }
}
