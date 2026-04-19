<?php

namespace App\Http\Requests\Api\V1\EquipmentType;

use Illuminate\Foundation\Http\FormRequest;

class SyncEquipmentTypeFormConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Accept empty array, but must be present
            'form_assignments' => ['present', 'array'],
            'form_assignments.*.form_config_id' => ['required_with:form_assignments.*', 'string', 'exists:form_configs,id'],
            'form_assignments.*.display_order' => ['required_with:form_assignments.*', 'integer'],
        ];
    }
}
