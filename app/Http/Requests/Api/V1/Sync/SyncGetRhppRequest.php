<?php

namespace App\Http\Requests\Api\V1\Sync;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SyncGetRhppRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return in_array($role, ['abk', 'pic', 'manager', 'admin', 'finance'], true);
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Investor tidak dapat menggunakan fitur sinkronisasi offline ini.',
                'data' => null,
            ], 403)
        );
    }

    public function rules(): array
    {
        return [
            'last_sync_timestamp' => ['nullable', 'date'],
        ];
    }
}
