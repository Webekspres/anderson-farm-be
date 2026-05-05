<?php

namespace App\Http\Requests\Api\V1\Sync;

use App\Models\ProductionPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncPostDailyActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Root Array ──
            'headers' => ['required', 'array', 'min:1'],

            // ── Header Fields ──
            'headers.*.id' => ['required', 'uuid'],
            'headers.*.period_id' => ['required', 'uuid', 'exists:production_periods,id'],
            'headers.*.user_id' => ['required', 'uuid', 'exists:users,id'],
            'headers.*.date' => ['required', 'date', 'before_or_equal:today'],
            'headers.*.age_days' => ['required', 'integer', 'min:0'],
            'headers.*.mortality_count' => ['integer', 'min:0'],
            'headers.*.cull_count' => ['integer', 'min:0'],
            'headers.*.average_weight' => ['nullable', 'numeric', 'min:0'],
            'headers.*.business_status' => ['required', 'string'],
            'headers.*.created_at_client' => ['required', 'date'],
            'headers.*.updated_at_client' => ['required', 'date'],

            // ── Dynamic Logs ──
            'headers.*.dynamic_logs' => ['nullable', 'array'],
            'headers.*.dynamic_logs.*.id' => ['required', 'uuid'],
            'headers.*.dynamic_logs.*.form_config_id' => ['required', 'uuid'],
            'headers.*.dynamic_logs.*.value' => ['required', 'string'],
            'headers.*.dynamic_logs.*.created_at_client' => ['required', 'date'],
            'headers.*.dynamic_logs.*.updated_at_client' => ['required', 'date'],

            // ── Harvest Entries ──
            'headers.*.harvests' => ['nullable', 'array'],
            'headers.*.harvests.*.id' => ['required', 'uuid'],
            'headers.*.harvests.*.rit_number' => ['required', 'integer', 'min:1'],
            'headers.*.harvests.*.buyer_name' => ['nullable', 'string', 'max:255'],
            'headers.*.harvests.*.total_birds' => ['required', 'integer', 'min:0'],
            'headers.*.harvests.*.total_weight' => ['required', 'numeric', 'min:0'],
            'headers.*.harvests.*.price_per_kg' => ['required', 'numeric', 'min:0'],
            'headers.*.harvests.*.total_revenue' => ['required', 'numeric', 'min:0'],
            'headers.*.harvests.*.delivery_order_no' => ['nullable', 'string', 'max:255'],
            'headers.*.harvests.*.created_at_client' => ['required', 'date'],
            'headers.*.harvests.*.updated_at_client' => ['required', 'date'],

            // ── OVK Usages ──
            'headers.*.ovk_usages' => ['nullable', 'array'],
            'headers.*.ovk_usages.*.id' => ['required', 'uuid'],
            'headers.*.ovk_usages.*.ovk_item_id' => ['required', 'uuid', 'exists:ovk_items,id'],
            'headers.*.ovk_usages.*.quantity' => ['required', 'numeric', 'min:0'],
            'headers.*.ovk_usages.*.notes' => ['nullable', 'string'],
            'headers.*.ovk_usages.*.created_at_client' => ['required', 'date'],
            'headers.*.ovk_usages.*.updated_at_client' => ['required', 'date'],

            // ── Photo Evidences ──
            'headers.*.photos' => ['nullable', 'array'],
            'headers.*.photos.*.id' => ['required', 'uuid'],
            'headers.*.photos.*.photo_path_local' => ['required', 'string', 'max:500'],
            'headers.*.photos.*.photo_url' => ['required', 'string', 'max:500'],
            'headers.*.photos.*.photo_type' => ['required', 'string', 'max:50'],
            'headers.*.photos.*.description' => ['nullable', 'string'],
            'headers.*.photos.*.created_at_client' => ['required', 'date'],
            'headers.*.photos.*.updated_at_client' => ['required', 'date'],

            // ── Checklist Logs ──
            'headers.*.checklist_logs' => ['nullable', 'array'],
            'headers.*.checklist_logs.*.id' => ['required', 'uuid'],
            'headers.*.checklist_logs.*.task_id' => ['required', 'uuid'],
            'headers.*.checklist_logs.*.boolean_value' => ['nullable', 'boolean'],
            'headers.*.checklist_logs.*.text_value' => ['nullable', 'string'],
            'headers.*.checklist_logs.*.notes' => ['nullable', 'string'],
            'headers.*.checklist_logs.*.created_at_client' => ['required', 'date'],
            'headers.*.checklist_logs.*.updated_at_client' => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $headers = $this->input('headers', []);
            foreach ($headers as $index => $header) {
                if (! is_array($header) || empty($header['period_id']) || empty($header['date'])) {
                    continue;
                }

                $period = ProductionPeriod::query()->find($header['period_id']);
                if (! $period || ! $period->start_date) {
                    continue;
                }

                $activityDate = Carbon::parse($header['date'])->startOfDay();
                $startDate = Carbon::parse($period->start_date)->startOfDay();

                if ($activityDate->lt($startDate)) {
                    $validator->errors()->add(
                        "headers.{$index}.date",
                        'Tanggal aktivitas tidak boleh sebelum tanggal mulai periode.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'headers.required' => 'Payload headers wajib diisi.',
            'headers.*.id.required' => 'Setiap header wajib memiliki UUID.',
            'headers.*.period_id.required' => 'Period ID wajib diisi untuk setiap header.',
            'headers.*.period_id.exists' => 'Period ID tidak ditemukan di server.',
            'headers.*.user_id.required' => 'User ID wajib diisi untuk setiap header.',
            'headers.*.date.required' => 'Tanggal laporan wajib diisi.',
            'headers.*.date.before_or_equal' => 'Laporan harian tidak boleh menggunakan tanggal masa depan.',
        ];
    }
}
