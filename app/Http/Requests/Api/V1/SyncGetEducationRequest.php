<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SyncGetEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'last_sync_timestamp' => ['nullable', 'date_format:Y-m-d\TH:i:sP'],
        ];
    }
}
