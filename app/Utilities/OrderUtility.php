<?php

declare(strict_types=1);

namespace App\Utilities;

use App\DTOs\HydratedOrderItem;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class OrderUtility
{
    /**
     * @param  iterable<HydratedOrderItem|\App\Models\OrderItem>  $items
     */
    public static function calculateSubtotal(iterable $items): string
    {
        $subtotal = BigDecimal::zero();

        foreach ($items as $item) {
            $subtotal = $subtotal->plus($item instanceof HydratedOrderItem ? $item->totalPrice : $item->total_price);
        }

        return $subtotal->toScale(4, RoundingMode::HalfUp)->toString();
    }

    /**
     * @param  array<int, array<string, mixed>>  $itemsData
     * @return list<HydratedOrderItem>
     *
     * @throws ModelNotFoundException
     */
    public function hydrateItems(array $itemsData): array
    {
        /** @var list<int> $productIds */
        $productIds = array_column($itemsData, 'product_id');
        $variantIds = array_filter(array_column($itemsData, 'product_variant_id'));

        $products = Product::query()
            ->withFeaturedMedia()
            ->findMany($productIds)->keyBy('id');
        $variants = $variantIds === []
            ? collect()
            : ProductVariant::query()
                ->with(['media:' . Media::displaySelect()])
                ->findMany($variantIds)->keyBy('id');

        foreach ($productIds as $productId) {
            if (! $products->has($productId)) {
                throw new ModelNotFoundException()->setModel(Product::class, [$productId]);
            }
        }

        foreach ($variantIds as $variantId) {
            if (! $variants->has($variantId)) {
                throw new ModelNotFoundException()->setModel(ProductVariant::class, [$variantId]);
            }
        }

        return array_values(array_map(function (array $itemData) use ($products, $variants): HydratedOrderItem {
            $product = $products[$itemData['product_id']];
            assert($product instanceof Product);

            $variant = isset($itemData['product_variant_id'])
                ? $variants[$itemData['product_variant_id']]
                : null;
            assert($variant === null || $variant instanceof ProductVariant);

            $unitPrice = $this->resolveUnitPrice($product->price ?? '0.0000', $variant?->price);
            $quantity = (int) $itemData['quantity'];
            $totalPrice = BigDecimal::of($unitPrice)
                ->multipliedBy($quantity)
                ->toScale(4, RoundingMode::HalfUp)
                ->toString();

            return new HydratedOrderItem(
                product: $product,
                variant: $variant,
                unitPrice: $unitPrice,
                totalPrice: $totalPrice,
                quantity: $quantity,
                variantOptions: $itemData['variant_options'] ?? null,
                productTitle: $product->getTranslations('title'),
                productSku: $variant->sku ?? $product->sku ?? '',
                variantTitle: $variant?->title,
                requiresShipping: $product->requiresShipping(),
                weight: $variant->weight ?? $product->weight,
                weightUnit: $variant->weight_unit ?? $product->weight_unit,
                length: $variant->length ?? $product->length,
                width: $variant->width ?? $product->width,
                height: $variant->height ?? $product->height,
                dimensionUnit: $variant->dimension_unit ?? $product->dimension_unit,
            );
        }, $itemsData));
    }

    public function calculateOrderTotal(
        string $subtotal,
        string $taxTotal,
        string $shippingTotal,
        string $discountTotal
    ): string {
        return BigDecimal::of($subtotal)
            ->plus($taxTotal)
            ->plus($shippingTotal)
            ->minus($discountTotal)
            ->toScale(4, RoundingMode::HalfUp)
            ->toString();
    }

    private function resolveUnitPrice(string $productPrice, ?string $variantPrice): string
    {
        return $variantPrice ?? $productPrice;
    }
}
