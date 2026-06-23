<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('transaction_category')?->id ?? null;
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('transaction_categories', 'name')->ignore($id)],
            'type' => ['required', Rule::in(['INCOME', 'EXPENSE'])],
            'is_active' => ['boolean'],
            'sync_status' => ['nullable', Rule::in(['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT'])],
            'created_at_client' => ['required', 'date'],
            'created_at_server' => ['nullable', 'date'],
            'updated_at_client' => ['required', 'date'],
            'updated_at_server' => ['nullable', 'date'],
        ];
    }
}
