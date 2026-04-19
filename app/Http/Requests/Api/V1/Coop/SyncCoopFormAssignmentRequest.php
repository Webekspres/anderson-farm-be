<?php

namespace App\Http\Requests\Api\V1\Coop;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncCoopFormAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */

    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    protected function failedAuthorization()
    {
        throw new UnauthorizedHttpException('sanctum', 'Unauthorized');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assignments' => ['present', 'array'],
            'assignments.*.coop_equipment_id' => ['required', 'string', 'exists:coop_equipments,id'],
            'assignments.*.form_config_id' => ['required', 'string', 'exists:form_configs,id'],
            'assignments.*.display_order' => ['required', 'integer'],
            'assignments.*.is_active' => ['boolean'],
        ];
    }
}
