<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;
use App\Models\User;

final class ResolveVisitorCartAction
{
    private ?Cart $cart = null;

    public function __construct(
        private readonly ResolveCartAction $resolveCartAction,
    ) {
    }

    public function handle(?string $cartId, ?User $customer): Cart
    {
        return $this->cart ??= $this->resolveCartAction->handle($cartId, $customer);
    }
}
