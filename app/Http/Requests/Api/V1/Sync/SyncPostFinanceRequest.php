<?php

namespace App\Http\Requests\Api\V1\Sync;

use Illuminate\Foundation\Http\FormRequest;

class SyncPostFinanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // RBAC: Tolak role abk dan investor sebelum validasi apapun dilakukan
        $role = $this->user()?->role;

        return !in_array($role, ['abk', 'investor']);
    }

    /**
     * Override failedAuthorization agar mengembalikan respons JSON standar (bukan redirect).
     */
    protected function failedAuthorization(): never
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Role Anda tidak diizinkan melakukan push transaksi.',
                'data'    => null,
            ], 403)
        );
    }

    public function rules(): array
    {
        return [
            // ── Root Fields ──
            'sync_timestamp'  => ['required', 'date'],
            'transactions'    => ['required', 'array', 'min:1'],

            // ── Per-Transaction Fields ──
            'transactions.*.id'                     => ['required', 'uuid'],
            'transactions.*.period_id'              => ['required', 'uuid'],
            'transactions.*.category_id'            => ['required', 'uuid', 'exists:transaction_categories,id'],
            'transactions.*.transaction_date'       => ['required', 'date'],
            'transactions.*.type'                   => ['required', 'string', 'in:EXPENSE,INCOME'],
            'transactions.*.amount'                 => ['required', 'numeric', 'min:0'],
            'transactions.*.description'            => ['nullable', 'string', 'max:1000'],
            'transactions.*.receipt_image_path_local' => ['nullable', 'string', 'max:500'],
            'transactions.*.created_at_client'      => ['required', 'date'],
            'transactions.*.updated_at_client'      => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'transactions.required'                   => 'Payload transactions wajib diisi.',
            'transactions.*.id.required'              => 'Setiap transaksi wajib memiliki UUID.',
            'transactions.*.id.uuid'                  => 'ID transaksi harus berformat UUID.',
            'transactions.*.period_id.required'       => 'Period ID wajib diisi untuk setiap transaksi.',
            'transactions.*.category_id.required'     => 'Category ID wajib diisi untuk setiap transaksi.',
            'transactions.*.category_id.exists'       => 'Category ID tidak ditemukan di server.',
            'transactions.*.type.in'                  => 'Tipe transaksi hanya boleh EXPENSE atau INCOME.',
            'transactions.*.amount.required'          => 'Jumlah transaksi wajib diisi.',
            'transactions.*.amount.numeric'           => 'Jumlah transaksi harus berupa angka.',
            'transactions.*.transaction_date.required' => 'Tanggal transaksi wajib diisi.',
        ];
    }
}
