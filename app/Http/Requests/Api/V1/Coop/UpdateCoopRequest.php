<?php

namespace App\Http\Requests\Api\V1\Coop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farm_id' => ['sometimes', 'uuid', 'exists:farms,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'floor' => ['sometimes', 'integer', 'min:1'],
            'coop_type' => ['sometimes', 'in:CH_POSTAL,CH_PLASTIC_SLAT,CH_MULTI_TIER'],
            'note' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sync_status' => ['nullable', 'in:LOCAL_SAVED,PENDING_SYNC,SYNCED,SYNC_FAILED,CONFLICT'],
            'created_at_client' => ['sometimes', 'date'],
            'created_at_server' => ['nullable', 'date'],
            'updated_at_client' => ['sometimes', 'date'],
            'updated_at_server' => ['nullable', 'date'],
            'sync_metadata' => ['nullable', 'string'],
        ];
    }
}
