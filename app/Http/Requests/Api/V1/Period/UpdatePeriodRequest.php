<?php

namespace App\Http\Requests\Api\V1\Period;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Ambil ID periode dari route parameter
        $periodId = $this->route('period_id');

        return [
            'floor_id'       => ['sometimes', 'uuid', 'exists:coop_floors,id'],
            'pic_id'        => ['sometimes', 'uuid', 'exists:users,id'],
            'start_date'    => ['sometimes', 'date'],
            'end_date'      => ['nullable', 'date', 'after_or_equal:start_date'],
            'initial_stock' => ['sometimes', 'integer', 'min:1'],

            // Validasi Unique dengan mengabaikan ID sendiri
            'period_code'   => [
                'sometimes',
                'string',
                Rule::unique('production_periods', 'period_code')->ignore($periodId)
            ],

            'status'         => ['sometimes', 'string', 'in:active,closed,draft'],
            'closing_reason' => ['nullable', 'string'],
            'updated_at_client' => ['required', 'date'], // Wajib dikirim untuk audit trail
        ];
    }
}
