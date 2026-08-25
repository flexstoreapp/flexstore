<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\StorefrontSection;
use Illuminate\Support\Facades\DB;

final readonly class ReorderStorefrontSectionsAction
{
    /**
     * @param  array<int, int>  $orderedIds
     */
    public function handle(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $sortOrder => $sectionId) {
                StorefrontSection::query()->whereKey($sectionId)->update(['sort_order' => $sortOrder]);
            }
        });
    }
}
