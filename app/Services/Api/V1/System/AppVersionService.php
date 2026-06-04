<?php

declare(strict_types=1);

namespace App\Services\Api\V1\System;

class AppVersionService
{
    /**
     * @return array{action: string, latest_version: string, update_url?: string}
     */
    public function evaluate(string $platform, string $currentVersion): array
    {
        $latestVersion = (string) config('app_version.latest');
        $minVersion = (string) config('app_version.min');

        $data = [
            'latest_version' => $latestVersion,
        ];

        if (version_compare($currentVersion, $minVersion, '<')) {
            return array_merge($data, [
                'action' => 'FORCE_UPDATE',
                'update_url' => (string) config("app_version.update_urls.{$platform}", ''),
            ]);
        }

        if (version_compare($currentVersion, $latestVersion, '<')) {
            return array_merge($data, [
                'action' => 'OPTIONAL_UPDATE',
            ]);
        }

        return array_merge($data, [
            'action' => 'NO_UPDATE_NEEDED',
        ]);
    }
}
