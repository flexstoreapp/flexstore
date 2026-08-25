<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

final readonly class HasPurchasedProduct implements ValidationRule
{
    public function __construct(
        private Product $product,
        private ?User $user,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->user instanceof User) {
            $fail(__('You must be logged in to review a product.'));

            return;
        }

        $hasPurchased = Order::query()
            ->where('customer_id', $this->user->id)
            ->whereIn('payment_status', PaymentStatus::eligibleForReview())
            ->whereHas('items', fn (Builder $query): Builder => $query->where('product_id', $this->product->id))
            ->exists();

        if (! $hasPurchased) {
            $fail(__('You can only review products you have purchased.'));
        }
    }
}
