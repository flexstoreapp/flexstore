<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\ProductVariant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class ValidProductVariant implements ValidationRule
{
    public function __construct(
        private int|string|null $productId,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        if (blank($this->productId)) {
            $fail(__('The selected product variant does not belong to the specified product.'));

            return;
        }

        $exists = ProductVariant::query()
            ->whereKey($value)
            ->where('product_id', $this->productId)
            ->exists();

        if (! $exists) {
            $fail(__('The selected product variant does not belong to the specified product.'));
        }
    }
}
