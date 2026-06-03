<?php

namespace App\Http\Requests\Api\V1\CoopFloor;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoopFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'coop_id' => ['required', 'uuid', 'exists:coops,id'],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
        ];
    }
}
