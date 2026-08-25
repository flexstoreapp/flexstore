<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Brand;
use App\Models\Media;

final readonly class BulkDestroyBrandAction
{
    /**
     * @param  array<int>  $brandIds
     */
    public function handle(array $brandIds): int
    {
        $brands = Brand::query()->whereIn('id', $brandIds)->get(['id', 'image_id']);

        $count = $brands->each->delete()->count();

        Media::deleteUnreferenced($brands->pluck('image_id')->all());

        return $count;
    }
}
