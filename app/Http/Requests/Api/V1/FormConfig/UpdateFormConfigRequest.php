<?php

namespace App\Http\Requests\Api\V1\FormConfig;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => 'sometimes|required|in:EQUIPMENT,HBE',
            'key_name' => 'sometimes|required|string|unique:form_configs,key_name,' . $this->route('form_config')?->id,
            'config_value' => 'sometimes|required|string',
            'sync_status' => 'nullable|in:LOCAL_SAVED,PENDING_SYNC,SYNCED,SYNC_FAILED,CONFLICT',
            'created_at_client' => 'sometimes|required|date',
            'created_at_server' => 'nullable|date',
            'updated_at_client' => 'sometimes|required|date',
            'updated_at_server' => 'nullable|date',
            'deleted_at' => 'nullable|date',
        ];
    }
}
