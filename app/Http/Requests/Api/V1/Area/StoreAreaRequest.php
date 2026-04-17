<?php
// app/Http/Requests/Api/V1/Area/StoreAreaRequest.php

namespace App\Http\Requests\Api\V1\Area;

use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:KANDANG,KEBUN,LAHAN'], // Enum, sesuaikan jika ada tambahan
            'size_m2' => ['nullable', 'numeric', 'min:0'],
            'manager_id' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
