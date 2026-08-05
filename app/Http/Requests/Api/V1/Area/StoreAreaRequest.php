<?php

namespace App\Http\Requests\Api\V1\Area;

use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('manager_id') && $this->user() !== null) {
            $this->merge([
                'manager_id' => $this->user()->id,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:KANDANG,KEBUN,LAHAN'],
            'size_m2' => ['nullable', 'numeric', 'min:0'],
            'manager_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }
}
