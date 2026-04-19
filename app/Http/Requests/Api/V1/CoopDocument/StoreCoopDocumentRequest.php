<?php

namespace App\Http\Requests\Api\V1\CoopDocument;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoopDocumentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'document_name' => 'required|string',
            'document_type' => 'required|string',
            'file_url' => 'required|url',
        ];
    }
}
