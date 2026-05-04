<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AdminResetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * (Sanctum handles auth, role check is in controller)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'new_password' => ['required', 'string', 'min:8'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'new_password.required' => 'Password baru harus diisi.',
            'new_password.string'   => 'Password baru harus berupa teks.',
            'new_password.min'      => 'Password baru minimal 8 karakter.',
        ];
    }
}
