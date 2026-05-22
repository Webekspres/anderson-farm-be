<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ExportRhppRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'period_id' => ['required', 'uuid'],
            'format' => ['required', 'string', 'in:pdf,excel'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'period_id.required' => 'ID periode harus diisi.',
            'period_id.uuid' => 'ID periode harus UUID yang valid.',
            'period_id.exists' => 'Periode tidak ditemukan.',
            'format.required' => 'Format export harus diisi (pdf atau excel).',
            'format.in' => 'Format harus berupa "pdf" atau "excel".',
        ];
    }
}
