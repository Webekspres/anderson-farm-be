<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormConfigStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'category' => 'required|in:EQUIPMENT,HBE',
            'key_name' => 'required|string|unique:form_configs,key_name',
            'config_value' => 'required|string',
            'sync_status' => 'nullable|in:LOCAL_SAVED,PENDING_SYNC,SYNCED,SYNC_FAILED,CONFLICT',
            'created_at_client' => 'required|date',
            'created_at_server' => 'nullable|date',
            'updated_at_client' => 'required|date',
            'updated_at_server' => 'nullable|date',
            'deleted_at' => 'nullable|date',
        ];
    }
}
