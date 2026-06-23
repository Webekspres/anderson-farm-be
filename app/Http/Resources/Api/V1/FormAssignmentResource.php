<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $formConfig = $this->relationLoaded('formConfig') ? $this->formConfig : null;

        return [
            'id' => $this->id,
            'form_config_id' => $this->form_config_id,
            'category' => $formConfig?->category,
            'key_name' => $formConfig?->key_name,
            'display_order' => (int) $this->display_order,
            'ui_metadata' => self::resolveUiMetadata($formConfig?->config_value),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolveUiMetadata(mixed $configValue): array
    {
        if (is_array($configValue)) {
            return $configValue;
        }

        if (! is_string($configValue) || trim($configValue) === '') {
            return [];
        }

        $decoded = json_decode($configValue, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return [];
        }

        return $decoded;
    }
}
