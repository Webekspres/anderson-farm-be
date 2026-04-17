<?php

namespace App\Http\Requests\Api\V1\EquipmentType;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'unique:equipment_types,name'],
            'description' => ['nullable', 'string'],
            'created_at_client' => ['required', 'date'],
            'updated_at_client' => ['required', 'date'],
        ];
    }
}
