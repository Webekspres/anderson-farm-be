<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // Ambil server_id dari route PATCH /users/{server_id}
        $userId = $this->route('server_id');
        if (is_object($userId) && property_exists($userId, 'server_id')) {
            $userId = $userId->server_id;
        }
        return [
            'username'   => [
                'sometimes',
                'string',
                Rule::unique('users', 'username')->ignore($userId, 'server_id'),
            ],
            'password'   => ['sometimes', 'string', 'min:8'],
            'name'       => ['sometimes', 'string'],
            'email'      => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($userId, 'server_id'),
            ],
            'phone'      => ['sometimes', 'string'],
            'role'       => ['sometimes', Rule::in(['admin', 'manager', 'finance', 'pic', 'abk', 'investor'])],
            'is_active'  => ['sometimes', 'boolean'],
            'device_id'  => ['sometimes', 'nullable', 'string'],
        ];
    }
}
