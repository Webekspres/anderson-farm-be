<?php

namespace App\Enums;

enum BusinessStatus: string
{
    case Draft = 'DRAFT';
    case Submitted = 'SUBMITTED';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case NeedsReview = 'NEEDS_REVIEW';

    /**
     * Status yang boleh di-push ABK melalui sync offline.
     *
     * @return list<string>
     */
    public static function syncableValues(): array
    {
        return [
            self::Draft->value,
            self::Submitted->value,
            self::NeedsReview->value,
        ];
    }

    /**
     * Status yang dianggap belum final (menggantung) untuk penutupan periode.
     *
     * @return list<string>
     */
    public static function pendingValues(): array
    {
        return [
            self::Draft->value,
            self::Submitted->value,
            self::NeedsReview->value,
        ];
    }
}
