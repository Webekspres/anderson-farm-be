<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi di-handle oleh Sanctum
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:255'],
            'role'     => ['required', 'in:admin,manager,finance,pic,abk,investor'],
        ];
    }
}
