<?php

namespace App\Http\Requests\Api\V1\Sync;

use Illuminate\Foundation\Http\FormRequest;

class SyncPostMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Semua role yang terautentikasi boleh mencoba push;
        // logika akses granular ditangani di Service layer.
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Root Fields ──
            'sync_timestamp'  => ['required', 'date'],
            'maintenances'    => ['required', 'array', 'min:1'],

            // ── Per-Log Fields ──
            'maintenances.*.id'               => ['required', 'uuid'],
            'maintenances.*.floor_id'         => ['required', 'uuid', 'exists:coop_floors,id'],
            'maintenances.*.description'      => ['required', 'string', 'max:2000'],
            'maintenances.*.status'           => ['required', 'string', 'in:REPORTED,IN_PROGRESS,RESOLVED'],
            'maintenances.*.image_path_local' => ['nullable', 'string', 'max:500'],
            'maintenances.*.created_at_client' => ['required', 'date'],
            'maintenances.*.updated_at_client' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'maintenances.required'                  => 'Payload maintenances wajib diisi.',
            'maintenances.*.id.required'             => 'Setiap log wajib memiliki UUID.',
            'maintenances.*.id.uuid'                 => 'ID log harus berformat UUID.',
            'maintenances.*.floor_id.required'       => 'Floor ID wajib diisi untuk setiap log.',
            'maintenances.*.floor_id.exists'         => 'Floor ID tidak ditemukan di server.',
            'maintenances.*.description.required'    => 'Deskripsi kerusakan wajib diisi.',
            'maintenances.*.status.in'               => 'Status hanya boleh REPORTED, IN_PROGRESS, atau RESOLVED.',
            'maintenances.*.created_at_client.required' => 'Timestamp client wajib diisi.',
        ];
    }
}
