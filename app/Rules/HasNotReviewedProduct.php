<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class HasNotReviewedProduct implements ValidationRule
{
    public function __construct(
        private Product $product,
        private ?User $user,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->user instanceof User) {
            return;
        }

        $hasReviewed = Review::query()
            ->where('product_id', $this->product->id)
            ->where('user_id', $this->user->id)
            ->exists();

        if ($hasReviewed) {
            $fail(__('You have already reviewed this product.'));
        }
    }
}
