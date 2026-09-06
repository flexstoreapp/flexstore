<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ProductRelationType;
use App\Enums\ProductSource;
use App\Models\Product;

final readonly class ProductRelationsQuery
{
    public function __construct(
        private SectionProductsQuery $sectionProductsQuery,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function execute(Product $product, ProductRelationType $type): array
    {
        $ids = $product->relatedProductsOfType($type)
            ->pluck('products.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($ids === []) {
            return [];
        }

        return $this->sectionProductsQuery->execute([
            'product_source' => ProductSource::Featured->value,
            'product_ids' => $ids,
        ]);
    }
}
