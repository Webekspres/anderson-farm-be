<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckVersionRequest extends FormRequest
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
        return [
            'platform' => ['required', 'string', Rule::in(['android', 'ios'])],
            'current_version' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'platform.required' => 'Platform wajib diisi.',
            'platform.in' => 'Platform harus android atau ios.',
            'current_version.required' => 'Versi aplikasi saat ini wajib diisi.',
            'current_version.string' => 'Versi aplikasi tidak valid.',
        ];
    }
}
