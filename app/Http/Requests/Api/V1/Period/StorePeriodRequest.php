<?php

namespace App\Http\Requests\Api\V1\Period;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ProductionPeriod;

class StorePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coop_id' => [
                'required',
                'string',
                'exists:coops,id',
                // Aturan Bisnis: Pastikan kandang ini tidak sedang dipakai oleh periode yang masih aktif
                Rule::unique('production_periods')->where(function ($query) {
                    return $query->where('status', 'active');
                })
            ],
            'pic_id' => ['required', 'string', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'initial_stock' => ['required', 'integer', 'min:1'],
            'period_code' => ['string', Rule::unique('production_periods', 'period_code')],
            'created_at_client' => ['required', 'date'],
            'updated_at_client' => ['nullable', 'date'],
            'closing_reason' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $coopId = $this->input('coop_id');
            if ($coopId) {
                $hasActive = ProductionPeriod::where('coop_id', $coopId)
                    ->where('status', 'active')
                    ->exists();
                if ($hasActive) {
                    $validator->errors()->add('coop_id', 'Kandang ini masih memiliki periode yang aktif. Tutup periode sebelumnya terlebih dahulu.');
                }
            }
        });
    }
}
