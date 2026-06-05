<?php

namespace App\Http\Requests\Api\V1;

use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use Illuminate\Foundation\Http\FormRequest;

class ExportHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()?->role === 'admin') {
            return true;
        }

        $periodId = $this->input('period_id');
        if (! $periodId) {
            return true;
        }

        $period = ProductionPeriod::query()
            ->with(['floor.coop.farm.area'])
            ->find($periodId);
        if (! $period) {
            return true;
        }

        $coopId = $period->floor?->coop_id;
        if (! $coopId) {
            return false;
        }

        $user = $this->user();

        if ($user->role === 'manager') {
            $areaManagerId = $period->floor?->coop?->farm?->area?->manager_id;
            if ($areaManagerId === $user->id) {
                return true;
            }
        }

        return CoopUserAssignment::query()
            ->where('user_id', $user->id)
            ->where('coop_id', $coopId)
            ->exists();
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
            'period_id.required' => 'ID periode harus diisi.',
            'period_id.uuid' => 'ID periode harus UUID yang valid.',
            'period_id.exists' => 'Periode tidak ditemukan.',
        ];
    }
}
