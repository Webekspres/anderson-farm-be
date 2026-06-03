<?php

namespace App\Http\Requests\Api\V1\Period;

use App\Models\ProductionPeriod;
use Illuminate\Foundation\Http\FormRequest;

class IndexPeriodChecklistTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'period_id' => $this->route('period_id'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period_id' => ['required', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period_id.required' => 'ID periode wajib diisi.',
            'period_id.uuid' => 'ID periode harus berupa UUID yang valid.',
        ];
    }

    public function period(): ProductionPeriod
    {
        return ProductionPeriod::query()->findOrFail($this->validated('period_id'));
    }
}
