<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProductRelationType;
use App\Models\Product;

final readonly class SyncProductRelationsAction
{
    /**
     * @param  list<int>  $relatedProductIds
     */
    public function handle(Product $product, ProductRelationType $type, array $relatedProductIds): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map(intval(...), $relatedProductIds),
            fn (int $id): bool => $id !== $product->id,
        )));

        $payload = [];

        foreach ($ids as $index => $id) {
            $payload[$id] = ['relation_type' => $type->value, 'sort_order' => $index];
        }

        $product->relatedProductsOfType($type)->sync($payload);
    }
}
