<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'current_password'      => ['required', 'string'],
            'new_password'          => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
