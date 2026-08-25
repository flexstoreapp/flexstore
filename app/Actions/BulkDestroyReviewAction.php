<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Review;

final readonly class BulkDestroyReviewAction
{
    /**
     * @param  array<int>  $reviewIds
     */
    public function handle(array $reviewIds): int
    {
        return Review::query()->whereIn('id', $reviewIds)->delete();
    }
}
