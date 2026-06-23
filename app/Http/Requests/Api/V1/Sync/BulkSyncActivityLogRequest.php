<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Sync;

use Illuminate\Foundation\Http\FormRequest;

class BulkSyncActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'logs' => ['present', 'array'],
            'logs.*.id' => ['required', 'uuid'],
            'logs.*.action' => ['required', 'string', 'max:255'],
            'logs.*.entity_type' => ['required', 'string', 'max:255'],
            'logs.*.entity_id' => ['required', 'string', 'max:255'],
            'logs.*.device_id' => ['nullable', 'string', 'max:255'],
            'logs.*.status' => ['required', 'string', 'max:50'],
            'logs.*.metadata' => ['nullable', 'string'],
            'logs.*.created_at_client' => ['required', 'date'],
            'logs.*.updated_at_client' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logs.present' => 'Daftar log aktivitas wajib dikirim.',
            'logs.array' => 'Format log aktivitas tidak valid.',
            'logs.*.id.required' => 'ID log wajib diisi.',
            'logs.*.id.uuid' => 'ID log harus berupa UUID yang valid.',
            'logs.*.action.required' => 'Aksi log wajib diisi.',
            'logs.*.entity_type.required' => 'Tipe entitas wajib diisi.',
            'logs.*.entity_id.required' => 'ID entitas wajib diisi.',
            'logs.*.status.required' => 'Status log wajib diisi.',
            'logs.*.created_at_client.required' => 'created_at_client wajib diisi.',
            'logs.*.updated_at_client.required' => 'updated_at_client wajib diisi.',
        ];
    }
}
