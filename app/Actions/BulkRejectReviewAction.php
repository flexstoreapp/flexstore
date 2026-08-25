<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReviewStatus;
use App\Models\Review;

final readonly class BulkRejectReviewAction
{
    /**
     * @param  array<int>  $reviewIds
     */
    public function handle(array $reviewIds): int
    {
        return Review::query()
            ->whereIn('id', $reviewIds)
            ->update(['status' => ReviewStatus::Rejected]);
    }
}
