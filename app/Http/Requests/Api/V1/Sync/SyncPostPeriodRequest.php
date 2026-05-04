<?php

namespace App\Http\Requests\Api\V1\Sync;

use Illuminate\Foundation\Http\FormRequest;

class SyncPostPeriodRequest extends FormRequest
{
    /**
     * Hanya ABK dan PIC yang mengirim persetujuan kontrak lapangan.
     */
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return in_array($role, ['abk', 'pic'], true);
    }

    /**
     * Respons JSON standar untuk penolakan RBAC (403).
     */
    protected function failedAuthorization(): never
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Hanya ABK dan PIC yang dapat mengirim persetujuan kontrak.',
                'data' => null,
            ], 403)
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sync_timestamp' => ['required', 'date'],
            // present + min:1 karena rule required Laravel menolak array kosong [] sebelum min
            'contract_acceptances' => ['present', 'array', 'min:1'],
            'contract_acceptances.*.id' => ['required', 'string', 'uuid'],
            'contract_acceptances.*.contract_id' => ['required', 'string', 'uuid'],
            'contract_acceptances.*.accepted_at' => ['required', 'date'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'sync_timestamp.required' => 'Waktu sinkronisasi harus diisi.',
            'sync_timestamp.date' => 'Waktu sinkronisasi harus tanggal/waktu yang valid (ISO-8601).',
            'contract_acceptances.present' => 'Array penerimaan kontrak harus dikirim.',
            'contract_acceptances.array' => 'Penerimaan kontrak harus berupa array.',
            'contract_acceptances.min' => 'Setidaknya satu penerimaan kontrak harus dikirim.',
            'contract_acceptances.*.id.required' => 'ID penerimaan kontrak harus diisi.',
            'contract_acceptances.*.id.uuid' => 'ID penerimaan kontrak harus UUID yang valid.',
            'contract_acceptances.*.contract_id.required' => 'ID kontrak harus diisi.',
            'contract_acceptances.*.contract_id.uuid' => 'ID kontrak harus UUID yang valid.',
            'contract_acceptances.*.accepted_at.required' => 'Waktu penerimaan harus diisi.',
            'contract_acceptances.*.accepted_at.date' => 'Waktu penerimaan harus tanggal/waktu yang valid (ISO-8601).',
        ];
    }
}
