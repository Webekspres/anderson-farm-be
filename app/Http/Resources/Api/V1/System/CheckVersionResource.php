<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array{action: string, latest_version: string, update_url?: string}
 */
class CheckVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'action' => $this->resource['action'],
            'latest_version' => $this->resource['latest_version'],
        ];

        if (array_key_exists('update_url', $this->resource)) {
            $data['update_url'] = $this->resource['update_url'];
        }

        return $data;
    }
}
