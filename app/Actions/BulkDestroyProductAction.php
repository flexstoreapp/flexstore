<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Media;
use App\Models\Product;

final readonly class BulkDestroyProductAction
{
    /**
     * @param  array<int>  $productIds
     */
    public function handle(array $productIds): int
    {
        $products = Product::query()
            ->with('mediaGallery:id', 'variants:id,product_id,media_id', 'downloads:id,product_id,media_id')
            ->whereIn('id', $productIds)
            ->get();

        $mediaIds = [
            ...$products->flatMap->mediaGallery->pluck('id'),
            ...$products->flatMap->variants->pluck('media_id'),
            ...$products->flatMap->downloads->pluck('media_id'),
        ];

        $count = $products->each->delete()->count();

        Media::deleteUnreferenced($mediaIds);

        return $count;
    }
}
