<?php

namespace App\Http\Requests\Api\V1\Period;

use Illuminate\Foundation\Http\FormRequest;

class ActivatePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // RBAC & assignment check ditangani di Service layer
    }

    public function rules(): array
    {
        return [
            'sync_timestamp' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'sync_timestamp.required' => 'Timestamp sinkronisasi wajib diisi.',
        ];
    }
}
