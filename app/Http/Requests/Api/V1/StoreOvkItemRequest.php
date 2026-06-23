<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOvkItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:ovk_items,name'],
            'type' => ['required', Rule::in(['OBAT', 'VAKSIN', 'KIMIA'])],
            'unit' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sync_status' => ['nullable', Rule::in(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT'])],
            'created_at_client' => ['required', 'date'],
            'created_at_server' => ['nullable', 'date'],
            'updated_at_client' => ['required', 'date'],
            'updated_at_server' => ['nullable', 'date'],
        ];
    }
}
