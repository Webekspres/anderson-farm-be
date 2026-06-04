<?php

declare(strict_types=1);

describe('GET /api/v1/system/check-version', function (): void {
    beforeEach(function (): void {
        config([
            'app_version.latest' => '1.3.0',
            'app_version.min' => '1.2.0',
            'app_version.update_urls' => [
                'android' => 'https://play.google.com/store/apps/details?id=com.andersonfarm.app',
                'ios' => 'https://apps.apple.com/app/anderson-farm/id000000000',
            ],
        ]);
    });

    it('mengembalikan FORCE_UPDATE ketika current_version di bawah min', function (): void {
        $response = $this->getJson('/api/v1/system/check-version?platform=android&current_version=1.1.0');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'action' => 'FORCE_UPDATE',
                    'latest_version' => '1.3.0',
                    'update_url' => 'https://play.google.com/store/apps/details?id=com.andersonfarm.app',
                ],
            ]);
    });

    it('mengembalikan OPTIONAL_UPDATE ketika current_version di atas min tetapi di bawah latest', function (): void {
        $response = $this->getJson('/api/v1/system/check-version?platform=ios&current_version=1.2.5');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'action' => 'OPTIONAL_UPDATE',
                    'latest_version' => '1.3.0',
                ],
            ])
            ->assertJsonMissingPath('data.update_url');
    });

    it('mengembalikan NO_UPDATE_NEEDED ketika current_version sama dengan latest', function (): void {
        $response = $this->getJson('/api/v1/system/check-version?platform=android&current_version=1.3.0');

        $response->assertOk()
            ->assertJsonPath('data.action', 'NO_UPDATE_NEEDED')
            ->assertJsonPath('data.latest_version', '1.3.0');
    });

    it('mengembalikan NO_UPDATE_NEEDED ketika current_version lebih tinggi dari latest', function (): void {
        $response = $this->getJson('/api/v1/system/check-version?platform=android&current_version=2.0.0');

        $response->assertOk()
            ->assertJsonPath('data.action', 'NO_UPDATE_NEEDED');
    });

    it('menolak request tanpa platform', function (): void {
        $this->getJson('/api/v1/system/check-version?current_version=1.2.0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['platform']);
    });

    it('menolak request tanpa current_version', function (): void {
        $this->getJson('/api/v1/system/check-version?platform=android')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_version']);
    });

    it('menolak platform yang tidak valid', function (): void {
        $this->getJson('/api/v1/system/check-version?platform=windows&current_version=1.2.0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['platform']);
    });

    it('dapat diakses tanpa autentikasi', function (): void {
        $this->getJson('/api/v1/system/check-version?platform=android&current_version=1.3.0')
            ->assertOk()
            ->assertJsonPath('success', true);
    });
});
