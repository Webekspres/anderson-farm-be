<?php

namespace App\Http\Requests\Api\V1\CoopEquipment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoopEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $coop = $this->route('coop');
        $coopId = $coop instanceof \App\Models\Coop ? $coop->id : $coop;

        return [
            'equipment_type_id' => ['required', 'exists:equipment_types,id'],
            'unit_code' => [
                'nullable',
                'string',
                Rule::unique('coop_equipments')->where(function ($query) use ($coopId) {
                    return $query->where('coop_id', $coopId);
                }),
            ],
            'installed_at' => ['nullable', 'date'],
            'created_at_client' => ['nullable', 'date'],
            'updated_at_client' => ['nullable', 'date'],
        ];
    }
}
