<?php

namespace App\Http\Requests\Api\V1\CoopFloor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoopFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'coop_id' => ['sometimes', 'required', 'uuid', 'exists:coops,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
        ];
    }
}
