<?php

namespace App\Http\Requests\Api\V1\Upload;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'folder' => 'required|string|in:contracts,documents,rhpp,photos,sop,articles,prices',
        ];
    }
}
