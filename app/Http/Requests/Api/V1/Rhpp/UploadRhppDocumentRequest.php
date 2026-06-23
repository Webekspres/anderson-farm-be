<?php

namespace App\Http\Requests\Api\V1\Rhpp;

use Illuminate\Foundation\Http\FormRequest;

class UploadRhppDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // RBAC: hanya manager dan admin — role lain ditolak sebelum validasi
        $role = $this->user()?->role;

        return in_array($role, ['manager', 'admin']);
    }

    /**
     * Kembalikan respons JSON standar saat authorization gagal (bukan redirect).
     */
    protected function failedAuthorization(): never
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Manager atau Admin yang dapat mengunggah dokumen RHPP.',
                'data'    => null,
            ], 403)
        );
    }

    public function rules(): array
    {
        return [
            'document'      => ['required', 'file', 'mimes:pdf', 'max:5120'],  // Max 5MB
            'total_income'  => ['required', 'numeric', 'min:0'],
            'total_expense' => ['required', 'numeric', 'min:0'],
            'net_profit'    => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'File dokumen RHPP wajib diunggah.',
            'document.mimes'    => 'Dokumen harus berformat PDF.',
            'document.max'      => 'Ukuran file tidak boleh melebihi 5MB.',
            'total_income.required'  => 'Total pemasukan wajib diisi.',
            'total_expense.required' => 'Total pengeluaran wajib diisi.',
            'net_profit.required'    => 'Laba bersih wajib diisi.',
        ];
    }
}
