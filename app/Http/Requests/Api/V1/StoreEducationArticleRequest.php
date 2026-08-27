<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreEducationArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'excerpt' => ['nullable', 'string'],
            'content_html' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:64'],
            'author_name' => ['nullable', 'string', 'max:128'],
            'link_url' => ['nullable', 'url'],
            'image_url' => ['nullable', 'url'],
            'image_path_local' => ['nullable', 'string'],
            'created_at_client' => ['nullable', 'date'],
            'updated_at_client' => ['nullable', 'date'],
        ];
    }
}
