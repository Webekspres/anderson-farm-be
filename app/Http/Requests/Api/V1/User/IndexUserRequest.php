<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi di-handle oleh middleware auth:sanctum
    }

    public function rules(): array
    {
        return [
            'search'    => ['nullable', 'string', 'max:255'],
            'role'      => ['nullable', 'string', 'in:admin,manager,finance,pic,abk,investor'],
            'is_active' => ['nullable', 'boolean'],
            'page'      => ['nullable', 'integer', 'min:1'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'    => ['nullable', 'string'],
            'limit'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
