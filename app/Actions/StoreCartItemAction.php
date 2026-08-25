<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreCartItemInput;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Queries\AvailableStockQuery;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class StoreCartItemAction
{
    public function __construct(
        private RecalculateCartTotalsAction $recalculateCartTotalsAction,
        private ResolveCartAction $resolveCartAction,
        private AvailableStockQuery $availableStockQuery,
    ) {
    }

    public function handle(?string $cartId, StoreCartItemInput $input, ?User $user = null, bool $recalculate = true): Cart
    {
        return DB::transaction(function () use ($cartId, $input, $user, $recalculate): Cart {
            $cart = $this->resolveCartAction->handle($cartId, $user);

            $product = Product::query()
                ->whereKey($input->productId)
                ->where('is_active', true)
                ->firstOrFail();

            $variant = $this->resolveVariant($product, $input->productVariantId);
            $quantity = max(1, $input->quantity);
            $existingItem = $this->findExistingItem($cart, $product->id, $variant?->id);

            $requestedQuantity = $quantity + ($existingItem->quantity ?? 0);
            $this->assertStockIsAvailable($product, $variant, $requestedQuantity);

            $unitPrice = $this->determineUnitPrice($product, $variant);
            $unitPriceFormatted = $unitPrice->toScale(4, RoundingMode::HalfUp)->toString();
            $compareAtPrice = $this->determineCompareAtPrice($product, $variant, $unitPrice);
            $totalPrice = $unitPrice
                ->multipliedBy($requestedQuantity)
                ->toScale(4, RoundingMode::HalfUp)
                ->toString();

            if ($existingItem instanceof CartItem) {
                $existingItem->update([
                    'quantity' => $requestedQuantity,
                    'unit_price' => $unitPriceFormatted,
                    'compare_at_price' => $compareAtPrice,
                    'total_price' => $totalPrice,
                    'variant_title' => $variant->title ?? $existingItem->variant_title,
                    'variant_options' => $input->variantOptions ?? $existingItem->variant_options,
                ]);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $requestedQuantity,
                    'unit_price' => $unitPriceFormatted,
                    'compare_at_price' => $compareAtPrice,
                    'total_price' => $totalPrice,
                    'variant_title' => $variant?->title,
                    'variant_options' => $input->variantOptions,
                ]);
            }

            return $recalculate ? $this->recalculateCartTotalsAction->handle($cart) : $cart;
        });
    }

    private function resolveVariant(Product $product, ?string $variantId): ?ProductVariant
    {
        if (in_array($variantId, [null, '', '0'], true)) {
            return null;
        }

        return ProductVariant::query()->where('product_id', $product->id)->findOrFail($variantId);
    }

    private function findExistingItem(Cart $cart, int $productId, ?string $variantId): ?CartItem
    {
        return $cart->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();
    }

    private function determineUnitPrice(Product $product, ?ProductVariant $variant): BigDecimal
    {
        return BigDecimal::of($variant->price ?? $product->price ?? '0.0000');
    }

    private function determineCompareAtPrice(
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

    private function assertStockIsAvailable(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        $target = $variant ?? $product;

        if (! $target->in_stock) {
            throw ValidationException::withMessages([
                'quantity' => __('This product is currently sold out.'),
            ]);
        }

        if (! $target->track_stock) {
            return;
        }

        $available = $this->availableStockQuery->execute($product->id, $variant?->id);

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => __('Requested quantity is unavailable in stock.'),
            ]);
        }
    }
}
