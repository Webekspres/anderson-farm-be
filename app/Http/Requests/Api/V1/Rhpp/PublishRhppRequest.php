<?php

namespace App\Http\Requests\Api\V1\Rhpp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PublishRhppRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role;
        return in_array($role, ['manager', 'admin']);
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Manager atau Admin yang dapat mempublikasikan RHPP.',
                'data'    => null,
            ], 403)
        );
    }

    public function rules(): array
    {
        return [
            'sync_timestamp' => ['required', 'date'],
        ];
    }
}
