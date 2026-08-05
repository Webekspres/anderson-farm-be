<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $method = $this->input('method');

        return [
            'method' => ['required', 'string', Rule::in(['old_password', 'admin_reset', 'otp'])],
            'username' => [
                Rule::requiredIf(in_array($method, ['old_password', 'otp'], true)),
                'prohibited_if:method,admin_reset',
                'string',
            ],
            'current_password' => [
                Rule::requiredIf($method === 'old_password'),
                'prohibited_if:method,admin_reset',
                'prohibited_if:method,otp',
                'string',
            ],
            'otp' => [
                Rule::requiredIf($method === 'otp'),
                'prohibited_if:method,old_password',
                'prohibited_if:method,admin_reset',
                'string',
                'size:6',
            ],
            'new_password' => [
                Rule::requiredIf(in_array($method, ['old_password', 'admin_reset', 'otp'], true)),
                'string',
                'min:8',
                Rule::when($method === 'old_password', ['confirmed', 'different:current_password']),
                Rule::when($method === 'otp', ['confirmed']),
            ],
            'new_password_confirmation' => [
                Rule::requiredIf(in_array($method, ['old_password', 'otp'], true)),
                'prohibited_if:method,admin_reset',
                'string',
            ],
            'user_id' => [
                Rule::requiredIf($method === 'admin_reset'),
                'prohibited_if:method,old_password',
                'prohibited_if:method,otp',
                'uuid',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'method.required' => 'Metode reset password wajib diisi.',
            'method.in' => 'Metode reset password tidak valid.',
            'username.required' => 'Username wajib diisi.',
            'current_password.required' => 'Password saat ini wajib diisi.',
            'otp.required' => 'OTP wajib diisi.',
            'otp.size' => 'OTP harus 6 digit.',
            'new_password.required' => 'Password baru harus diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'new_password.different' => 'Password baru harus berbeda dari password saat ini.',
            'new_password_confirmation.required' => 'Konfirmasi password baru wajib diisi.',
            'user_id.required' => 'ID user wajib diisi.',
            'user_id.uuid' => 'ID user tidak valid.',
        ];
    }
}
