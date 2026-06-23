<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFcmTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'fcm_token.required' => 'FCM token wajib diisi.',
            'fcm_token.string' => 'FCM token harus berupa string.',
        ];
    }
}
