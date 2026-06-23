<?php

namespace App\Http\Requests\Api\V1\EquipmentType;

use Illuminate\Foundation\Http\FormRequest;

class SyncEquipmentFormConfigsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'form_config_ids' => ['required', 'array', 'min:1'],
            'form_config_ids.*' => ['required', 'uuid', 'distinct', 'exists:form_configs,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'form_config_ids.required' => 'Daftar form config wajib diisi.',
            'form_config_ids.min' => 'Minimal satu form config harus dipilih.',
            'form_config_ids.*.exists' => 'Form config tidak ditemukan.',
            'form_config_ids.*.distinct' => 'Form config tidak boleh duplikat.',
        ];
    }
}
