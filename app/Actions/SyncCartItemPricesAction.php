<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

final readonly class SyncCartItemPricesAction
{
    public function handle(Cart $cart): Cart
    {
        $items = $cart->items;

        if ($items->isEmpty()) {
            return $cart;
        }

        return DB::transaction(function () use ($cart, $items): Cart {
            /** @var list<int> $productIds */
            $productIds = $items->pluck('product_id')->unique()->values()->all();
            $variantIds = $items->pluck('product_variant_id')->filter()->unique()->values()->all();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->get(['id', 'price', 'compare_at_price', 'category_id'])
                ->keyBy('id');

            $variants = $variantIds === []
                ? collect()
                : ProductVariant::query()
                    ->whereIn('id', $variantIds)
                    ->get(['id', 'price', 'compare_at_price'])
                    ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                $variant = $item->product_variant_id ? $variants->get($item->product_variant_id) : null;

                if (! $product) {
                    continue;
                }

                $currentPrice = $this->resolveUnitPrice(
                    $product->price ?? '0.0000',
                    $variant?->price,
                );

                $currentPriceDecimal = BigDecimal::of($currentPrice)->toScale(4, RoundingMode::HalfUp);
                $compareAtPrice = $this->resolveCompareAtPrice(
                    $product,
                    $variant,
                    $currentPriceDecimal,
                );

                $priceChanged = ! $currentPriceDecimal->isEqualTo(BigDecimal::of($item->unit_price));
                $compareAtChanged = $compareAtPrice !== $this->normalizeCompareAtPrice($item->compare_at_price);

                if ($priceChanged || $compareAtChanged) {
                    $totalPrice = $currentPriceDecimal
                        ->multipliedBy($item->quantity)
                        ->toScale(4, RoundingMode::HalfUp)
                        ->toString();

                    $item->update([
                        'unit_price' => $currentPriceDecimal->toString(),
                        'compare_at_price' => $compareAtPrice,
                        'total_price' => $totalPrice,
                    ]);
                }
            }

            return $cart->load('items');
        });
    }

    private function normalizeCompareAtPrice(?string $compareAtPrice): ?string
    {
        return $compareAtPrice === null
            ? null
            : BigDecimal::of($compareAtPrice)->toScale(4, RoundingMode::HalfUp)->toString();
    }

    private function resolveCompareAtPrice(
        Product $product,
        ?ProductVariant $variant,
        BigDecimal $unitPrice,
    ): ?string {
        $compareAt = $variant->compare_at_price ?? $product->compare_at_price;

        if ($compareAt === null) {
            return null;
        }

        $compareAtDecimal = BigDecimal::of($compareAt)->toScale(4, RoundingMode::HalfUp);

        return $compareAtDecimal->isGreaterThan($unitPrice) ? $compareAtDecimal->toString() : null;
    }

    private function resolveUnitPrice(string $productPrice, ?string $variantPrice): string
    {
        return $variantPrice ?? $productPrice;
    }
}
