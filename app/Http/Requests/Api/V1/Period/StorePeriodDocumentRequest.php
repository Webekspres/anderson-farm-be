<?php

namespace App\Http\Requests\Api\V1\Period;

use Illuminate\Foundation\Http\FormRequest;

class StorePeriodDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'in:OVK,ARV,OTHER,CARE_TEMPLATE'],
            // Minimal salah satu file_url atau local path harus ada
            'file_url' => ['required_without:file_path_local', 'nullable', 'url'],
            'file_path_local' => ['required_without:file_url', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.in' => 'Tipe dokumen harus salah satu dari: OVK, ARV, OTHER, atau CARE_TEMPLATE.',
            'file_url.required_without' => 'Bukti dokumen (URL/Path) wajib disertakan.',
        ];
    }
}
