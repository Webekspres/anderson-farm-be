<?php

namespace App\Http\Requests\Api\V1\Approval;

use App\Enums\BusinessStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class IndexDailyActivityApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'manager', 'finance'], true);
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Manager, Finance, atau Admin yang dapat mengakses modul approval.',
                'data' => null,
            ], 403)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period_id' => ['nullable', 'uuid'],
            'coop_id' => ['nullable', 'uuid'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'business_status' => [
                'nullable',
                'string',
                Rule::in([
                    BusinessStatus::Draft->value,
                    BusinessStatus::Submitted->value,
                    BusinessStatus::Approved->value,
                    BusinessStatus::Rejected->value,
                    BusinessStatus::NeedsReview->value,
                ]),
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('business_status')) {
            $this->merge(['business_status' => BusinessStatus::Submitted->value]);
        }
    }
}
