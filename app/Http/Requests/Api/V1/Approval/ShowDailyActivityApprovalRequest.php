<?php

namespace App\Http\Requests\Api\V1\Approval;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ShowDailyActivityApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'manager', 'finance'], true);
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Manager, Finance, atau Admin yang dapat mengakses modul approval.',
                'data' => null,
            ], 403)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
