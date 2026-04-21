<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SyncChecklistTaskRequest extends FormRequest
{
    /**
     * Tentukan apakah user memiliki otorisasi.
     */
    public function authorize(): bool
    {
        return true; // Asumsikan otorisasi ditangani oleh middleware/policy
    }

    /**
     * Aturan validasi untuk request sinkronisasi SOP.
     */
    public function rules(): array
    {
        return [
            'tasks'               => ['present', 'array'], // Boleh array kosong [] untuk menghapus semua
            'tasks.*.task_name'   => ['required', 'string', 'max:255'],
            'tasks.*.task_type'   => ['required', 'string', 'in:BOOLEAN,TEXT'],
            'tasks.*.description' => ['nullable', 'string'],
            'tasks.*.is_active'   => ['nullable', 'boolean'],
        ];
    }

    /**
     * Pesan error kustom untuk membantu Frontend.
     */
    public function messages(): array
    {
        return [
            'tasks.*.task_type.in' => 'Tipe tugas harus berupa BOOLEAN atau TEXT.',
        ];
    }
}
