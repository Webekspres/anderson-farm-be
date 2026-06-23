<?php

namespace App\Http\Requests\Api\V1\Period;

use Illuminate\Foundation\Http\FormRequest;

class SyncPeriodFormAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignments' => ['present', 'array'],
            'assignments.*.form_config_id' => ['required', 'string', 'exists:form_configs,id'],
            'assignments.*.display_order' => ['required', 'integer', 'min:1'],
            'assignments.*.is_active' => ['required', 'boolean'],
        ];
    }
}
