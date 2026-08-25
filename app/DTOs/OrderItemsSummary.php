<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Utilities\OrderUtility;
use App\Utilities\WeightConverter;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;

final readonly class OrderItemsSummary
{
    /**
     * @param  array<int, array{product_id: int, product_variant_id?: string|null, quantity: int, length_cm?: string|null, width_cm?: string|null, height_cm?: string|null, weight_grams?: string|null}>  $items
     * @param  Collection<int, Product>  $products
     * @param  Collection<string, ProductVariant>  $variants
     * @param  array<int>  $productIds
     * @param  array<int>  $categoryIds
     * @param  array<int>  $brandIds
     */
    public function __construct(
        public array $items,
        public Collection $products,
        public Collection $variants,
        public string $subtotal,
        public BigDecimal $totalWeightInGrams,
        public array $productIds,
        public array $categoryIds,
        public array $brandIds,
    ) {
    }

    /**
     * @param  list<HydratedOrderItem>  $hydratedItems
     */
    public static function fromHydratedItems(array $hydratedItems): self
    {
        $subtotal = BigDecimal::zero();
        $totalWeightInGrams = BigDecimal::zero();
        $productIds = [];
        $categoryIds = [];
        $brandIds = [];
        $products = collect();
        $variants = collect();

        foreach ($hydratedItems as $item) {
            $subtotal = $subtotal->plus($item->totalPrice);

            if ($item->weight !== null && $item->weightUnit !== null) {
                $itemWeight = BigDecimal::of($item->weight)->multipliedBy($item->quantity)->toString();
                $totalWeightInGrams = $totalWeightInGrams->plus(
                    WeightConverter::toGrams($itemWeight, $item->weightUnit)
                );
            }

            $productIds[] = $item->product->id;

            if ($item->product->category_id !== null) {
                $categoryIds[] = $item->product->category_id;
            }

            if ($item->product->brand_id !== null) {
                $brandIds[] = $item->product->brand_id;
            }
            $products[$item->product->id] = $item->product;

            if ($item->variant !== null) {
                $variants[$item->variant->id] = $item->variant;
            }
        }

        return new self(
            items: array_map(fn (HydratedOrderItem $item): array => [
                'product_id' => $item->product->id,
                'product_variant_id' => $item->variant?->id,
                'quantity' => $item->quantity,
            ], $hydratedItems),
            products: $products,
            variants: $variants,
            subtotal: $subtotal->toScale(4, RoundingMode::HalfUp)->toString(),
            totalWeightInGrams: $totalWeightInGrams,
            productIds: $productIds,
            categoryIds: array_values(array_unique($categoryIds)),
            brandIds: array_values(array_unique($brandIds)),
        );
    }

    /**
     * Test-only convenience factory: hydrates items from the container.
     * Production code must build via fromHydratedItems() with pre-hydrated items.
     *
     * @param  array<int, array{product_id: int, product_variant_id?: string|null, quantity: int, length_cm?: string|null, width_cm?: string|null, height_cm?: string|null, weight_grams?: string|null}>  $items
     */
    public static function fromItems(array $items): self
    {
        $hydratedItems = resolve(OrderUtility::class)->hydrateItems(
            array_map(fn (array $item): array => [
                ...$item,
                'variant_options' => [],
            ], $items),
        );

        return self::fromHydratedItems($hydratedItems);
    }

    public function requiresShipping(): bool
    {
        return $this->products->contains(
            fn (Product $product): bool => $product->requiresShipping(),
        );
    }
}
