<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final readonly class CanReviewProductQuery
{
    public function execute(Product $product, ?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $hasPurchased = Order::query()
            ->where('customer_id', $user->id)
            ->whereIn('payment_status', PaymentStatus::eligibleForReview())
            ->whereHas('items', fn (Builder $query): Builder => $query->where('product_id', $product->id))
            ->exists();

        if (! $hasPurchased) {
            return false;
        }

        return ! Review::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
