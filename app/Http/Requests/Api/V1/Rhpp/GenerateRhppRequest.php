<?php

namespace App\Http\Requests\Api\V1\Rhpp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GenerateRhppRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return in_array($role, ['manager', 'admin', 'finance'], true);
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Manager, Admin, atau Finance yang dapat menghitung RHPP.',
                'data' => null,
            ], 403)
        );
    }

    public function rules(): array
    {
        return [];
    }
}
