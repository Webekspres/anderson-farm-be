<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentFormAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'key_name' => $this->key_name,
            'ui_metadata' => self::resolveUiMetadata($this->config_value),
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
