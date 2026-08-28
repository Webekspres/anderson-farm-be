<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeDeviationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period_id' => ['required', 'string', 'exists:production_periods,id'],
            'metric' => ['required', 'string', 'max:64'],
            'date' => ['nullable', 'date'],
        ];
    }
}
