<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Investor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ShowInvestorPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return in_array($role, ['investor', 'admin'], true);
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Hanya investor (atau admin) yang dapat melihat detail periode investasi.',
                'data' => null,
            ], 403)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
