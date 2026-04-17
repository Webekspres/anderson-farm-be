<?php

namespace App\Http\Requests\Api\V1\EquipmentType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('equipment_type') ?? $this->route('id');
        return [
            'name' => [
                'sometimes',
                'string',
                Rule::unique('equipment_types', 'name')->ignore($id, 'id'),
            ],
            'description' => ['sometimes', 'string'],
        ];
    }
}
