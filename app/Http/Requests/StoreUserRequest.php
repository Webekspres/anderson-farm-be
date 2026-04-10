<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya Admin yang boleh membuat user baru (Otorisasi)
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'unique:users,username'],
            'email'     => ['nullable', 'email', 'unique:users,email'],
            'role'      => ['required', 'in:admin,manager,finance,pic,abk,investor'],
            'password'  => ['required', Password::defaults()],
        ];
    }
}
