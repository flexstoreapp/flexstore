<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Queries\AvailableStockQuery;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateCartItemAction
{
    public function __construct(
        private RecalculateCartTotalsAction $recalculateCartTotalsAction,
        private ResolveCartAction $resolveCartAction,
        private AvailableStockQuery $availableStockQuery,
    ) {
    }

    public function handle(?string $cartId, int $cartItemId, int $quantity, ?User $user = null): Cart
    {
        return DB::transaction(function () use ($cartId, $cartItemId, $quantity, $user): Cart {
            $cart = $this->resolveCartAction->handle($cartId, $user);

            $cartItem = CartItem::query()->findOrFail($cartItemId);

            if ($cart->id !== $cartItem->cart_id) {
                throw ValidationException::withMessages([
                    'item' => __('Cart item does not belong to this cart.'),
                ]);
            }

            $cartItem->loadMissing(['product', 'productVariant']);

            if ($quantity <= 0) {
                $cartItem->delete();

                return $this->recalculateCartTotalsAction->handle($cart);
            }

            $this->assertStockIsAvailable($cartItem, $quantity);

            $totalPrice = BigDecimal::of($cartItem->unit_price)
                ->multipliedBy($quantity)
                ->toScale(4, RoundingMode::HalfUp)
                ->toString();

            $cartItem->update([
                'quantity' => $quantity,
                'total_price' => $totalPrice,
            ]);

            return $this->recalculateCartTotalsAction->handle($cart);
        });
    }

    private function assertStockIsAvailable(CartItem $cartItem, int $quantity): void
    {
        $product = $cartItem->product;

        if ($product === null) {
            return;
        }

        $variant = $cartItem->productVariant;
        $target = $variant ?? $product;

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
