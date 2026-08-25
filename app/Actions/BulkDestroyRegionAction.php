<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Region;

final readonly class BulkDestroyRegionAction
{
    /**
     * @param  array<int>  $regionIds
     */
    public function handle(array $regionIds): int
    {
        return Region::query()->whereIn('id', $regionIds)->delete();
    }
}
