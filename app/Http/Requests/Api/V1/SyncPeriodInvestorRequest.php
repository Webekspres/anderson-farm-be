<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\ProductionPeriod;
use Illuminate\Support\Facades\Date;

class SyncPeriodInvestorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'investors' => ['present', 'array'],
            'investors.*.user_id' => [
                'required',
                'string',
                'exists:users,id',
            ],
            'investors.*.profit_share_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'investors.*.initial_investment' => ['nullable', 'numeric'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $investors = $this->input('investors', []);
            $totalShare = collect($investors)->sum('profit_share_percentage');
            if ($totalShare > 100) {
                $validator->errors()->add('investors', 'Total profit_share_percentage tidak boleh lebih dari 100%.');
            }

            // Validasi user_id harus role investor
            $userIds = collect($investors)->pluck('user_id')->unique()->all();
            $invalid = User::whereIn('id', $userIds)->where('role', '!=', 'investor')->pluck('id')->all();
            if (!empty($invalid)) {
                $validator->errors()->add('investors', 'Semua user_id harus user dengan role investor.');
            }

            // Validasi periode belum berjalan
            $periodId = $this->route('period_id');
            $period = ProductionPeriod::find($periodId);
            if (!$period) {
                $validator->errors()->add('period_id', 'Periode tidak ditemukan.');
                return;
            }
            $today = now()->startOfDay();
            if ($period->status !== 'active' || $period->start_date->lte($today)) {
                $validator->errors()->add('period_id', 'Investor tidak dapat diubah karena periode sudah berjalan atau tidak aktif.');
            }
        });
    }
}
