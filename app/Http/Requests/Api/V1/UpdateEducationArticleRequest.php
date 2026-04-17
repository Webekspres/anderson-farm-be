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
            'link_url' => ['sometimes', 'nullable', 'url'],
            'image_url' => ['sometimes', 'nullable', 'url'],
            'image_path_local' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
