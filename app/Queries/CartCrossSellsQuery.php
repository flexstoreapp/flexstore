<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ProductSource;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class CartCrossSellsQuery
{
    public function __construct(
        private SectionProductsQuery $sectionProductsQuery,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function execute(Cart $cart, int $limit): array
    {
        $cart->loadMissing('items');

        $cartProductIds = $cart->items->pluck('product_id')->unique()->values()->all();

        if ($cartProductIds === []) {
            return [];
        }

        $ids = Product::query()
            ->select('id')
            ->whereIn('id', $cartProductIds)
            ->with(['crossSells' => fn (Relation $relation): Relation => $relation->where('products.is_active', true)])
            ->get()
            ->flatMap(fn (Product $product): array => $product->crossSells->pluck('id')->all())
            ->unique()
            ->reject(fn (mixed $id): bool => in_array((int) $id, $cartProductIds, true))
            ->take($limit)
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
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
