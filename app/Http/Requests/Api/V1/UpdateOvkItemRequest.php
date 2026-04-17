<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOvkItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('ovk_item')?->id ?? $this->route('id');
        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('ovk_items', 'name')->ignore($id)],
            'type' => ['sometimes', Rule::in(['OBAT', 'VAKSIN', 'KIMIA'])],
            'unit' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sync_status' => ['nullable', Rule::in(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT'])],
            'created_at_client' => ['sometimes', 'date'],
            'created_at_server' => ['nullable', 'date'],
            'updated_at_client' => ['sometimes', 'date'],
            'updated_at_server' => ['nullable', 'date'],
        ];
    }
}
