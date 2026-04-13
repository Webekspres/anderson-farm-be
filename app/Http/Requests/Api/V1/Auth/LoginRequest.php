<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Siapapun boleh mencoba login
    }

    public function rules(): array
    {
        return [
            'username'  => ['required', 'string'],
            'password'  => ['required', 'string'],
            'device_id' => ['required', 'string'], // Wajib dikirim oleh HP Android
        ];
    }
}
