<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEducationArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string'],
            'excerpt' => ['sometimes', 'nullable', 'string'],
            'content_html' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', 'string', 'max:64'],
            'author_name' => ['sometimes', 'nullable', 'string', 'max:128'],
            'link_url' => ['sometimes', 'nullable', 'url'],
            'image_url' => ['sometimes', 'nullable', 'url'],
            'image_path_local' => ['sometimes', 'nullable', 'string'],
            'created_at_client' => ['nullable', 'date'],
            'updated_at_client' => ['nullable', 'date'],
        ];
    }
}
