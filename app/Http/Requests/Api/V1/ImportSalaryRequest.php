<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ImportSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['finance', 'admin'], true);
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Finance atau Admin yang dapat mengimpor data gaji.',
                'data' => null,
            ], 403)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ];
    }
}
