<?php

namespace App\Http\Requests\Api\V1\Sync;

use Illuminate\Foundation\Http\FormRequest;

class SyncGetDailyActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'uuid', 'exists:production_periods,id'],
            'last_sync_timestamp' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_id.required' => 'Period ID wajib diisi.',
            'period_id.uuid' => 'Format Period ID harus berupa UUID.',
            'period_id.exists' => 'Period ID tidak ditemukan.',
            'last_sync_timestamp.date' => 'Format timestamp tidak valid.',
        ];
    }
}
