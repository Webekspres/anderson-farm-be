<?php
// app/Http/Requests/Api/V1/ReportTemplate/StoreReportTemplateRequest.php

namespace App\Http\Requests\Api\V1\ReportTemplate;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:report_templates,name'],
            'report_type' => ['required', 'string', 'max:255'],
            'content_placeholder' => ['required', 'string'],
            'sync_status' => ['required', 'in:LOCAL_SAVED,PENDING_SYNC,SYNCED,SYNC_FAILED,CONFLICT'],
            'created_at_client' => ['required', 'date'],
            'updated_at_client' => ['required', 'date'],
        ];
    }
}
