<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Area;

use Illuminate\Foundation\Http\FormRequest;

class ShowAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'area' => $this->route('area'),
        ]);
    }

    public function rules(): array
    {
        return [
            'area' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'area.required' => 'ID area harus diisi.',
            'area.uuid' => 'ID area harus berupa UUID yang valid.',
        ];
    }
}
