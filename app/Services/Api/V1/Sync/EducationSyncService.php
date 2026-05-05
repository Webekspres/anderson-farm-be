<?php

namespace App\Services\Api\V1\Sync;

use App\Models\EducationArticle;
use App\Models\PriceReference;
use Carbon\Carbon;

class EducationSyncService
{
    public function getDeltaPayload(?string $lastSyncTimestamp): array
    {
        $since = $lastSyncTimestamp ? Carbon::parse($lastSyncTimestamp) : null;

        $educationArticles = EducationArticle::withTrashed()
            ->when($since, fn ($q) => $q->where('updated_at_server', '>', $since))
            ->orderBy('id')
            ->get();

        $priceReferences = PriceReference::withTrashed()
            ->when($since, fn ($q) => $q->where('updated_at_server', '>', $since))
            ->orderBy('id')
            ->get();

        return [
            'education_articles' => $educationArticles,
            'price_references' => $priceReferences,
        ];
    }
}
