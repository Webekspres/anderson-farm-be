<?php

namespace App\Http\Requests\Api\V1\Upload;

use Illuminate\Foundation\Http\FormRequest;

class DeleteFileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'file_path' => 'required|string',
        ];
    }
}
