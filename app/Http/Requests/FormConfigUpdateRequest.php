<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormConfigUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'category' => 'sometimes|required|in:EQUIPMENT,HBE',
            'key_name' => 'sometimes|required|string|unique:form_configs,key_name,' . $this->route('formConfig'),
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
