<?php

namespace App\Http\Requests\Api\V1\Period;

use App\Models\CoopFloor;
use App\Models\ProductionPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'floor_id' => [
                'required',
                'string',
                'exists:coop_floors,id',
                // Aturan Bisnis: Pastikan kandang ini tidak sedang dipakai oleh periode yang masih aktif
                Rule::unique('production_periods')->where(function ($query) {
                    return $query->where('status', 'active');
                }),
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $floorId = $this->input('floor_id');
            if (! $floorId) {
                return;
            }

            $hasActive = ProductionPeriod::where('floor_id', $floorId)
                ->where('status', 'active')
                ->exists();
            if ($hasActive) {
                $validator->errors()->add('floor_id', 'Kandang ini masih memiliki periode yang aktif. Tutup periode sebelumnya terlebih dahulu.');
            }

            $initialStock = $this->input('initial_stock');
            if ($initialStock === null) {
                return;
            }

            $capacity = CoopFloor::query()->whereKey($floorId)->value('capacity');
            if ($capacity && (int) $initialStock > (int) $capacity) {
                $validator->errors()->add(
                    'initial_stock',
                    "Stok awal (DOC) tidak boleh melebihi kapasitas lantai ({$capacity})."
                );
            }
        });
    }
}
