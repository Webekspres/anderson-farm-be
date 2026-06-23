<?php

namespace App\Http\Requests\Api\V1\Sync;

use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use Illuminate\Foundation\Http\FormRequest;

class SyncGetDailyActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $periodId = $this->input('period_id');
        if (! $periodId) {
            return true;
        }

        $period = ProductionPeriod::query()->with('floor')->find($periodId);
        if (! $period) {
            return true;
        }

        $coopId = $period->floor?->coop_id;
        if (! $coopId) {
            return false;
        }

        return CoopUserAssignment::query()
            ->where('user_id', $this->user()->id)
            ->where('coop_id', $coopId)
            ->exists();
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'uuid', 'exists:production_periods,id'],
            'last_sync_timestamp' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_id.required' => 'Period ID wajib diisi.',
            'period_id.uuid' => 'Format Period ID harus berupa UUID.',
            'period_id.exists' => 'Period ID tidak ditemukan.',
            'last_sync_timestamp.date' => 'Format timestamp tidak valid.',
        ];
    }
}
