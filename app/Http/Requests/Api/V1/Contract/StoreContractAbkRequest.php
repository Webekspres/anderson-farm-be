<?php

namespace App\Http\Requests\Api\V1\Contract;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractAbkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi ditangani oleh middleware
    }

    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            // Minimal salah satu harus diisi (URL online atau path lokal)
            'file_url'        => ['required_without:file_path_local', 'nullable', 'url'],
            'file_path_local' => ['required_without:file_url', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul kontrak wajib diisi.',
            'file_url.required_without' => 'URL file atau Path lokal wajib disertakan.',
        ];
    }
}
