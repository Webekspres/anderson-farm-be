<?php
// app/Http/Requests/Api/V1/Area/UpdateAreaRequest.php

namespace App\Http\Requests\Api\V1\Area;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:KANDANG,KEBUN,LAHAN'],
            'size_m2' => ['sometimes', 'numeric', 'min:0'],
            'manager_id' => ['sometimes', 'uuid', 'exists:users,id'],
        ];
    }
}
