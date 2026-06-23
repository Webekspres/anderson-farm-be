<?php
// app/Http/Requests/Api/V1/ReportTemplate/UpdateReportTemplateRequest.php

namespace App\Http\Requests\Api\V1\ReportTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('report_template') ?? $this->route('id');
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('report_templates', 'name')->ignore($id)
            ],
            'report_type' => ['sometimes', 'string', 'max:255'],
            'content_placeholder' => ['sometimes', 'string'],
        ];
    }
}
