<?php

namespace App\Http\Requests\Api\V1\Period;

use Illuminate\Foundation\Http\FormRequest;

class ClosePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // RBAC & assignment check ditangani di Service layer
    }

    public function rules(): array
    {
        return [
            'closing_reason' => ['required', 'string', 'min:5', 'max:1000'],
            'sync_timestamp' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'closing_reason.required' => 'Alasan penutupan wajib diisi.',
            'closing_reason.min'      => 'Alasan penutupan minimal 5 karakter.',
            'sync_timestamp.required' => 'Timestamp sinkronisasi wajib diisi.',
        ];
    }
}
