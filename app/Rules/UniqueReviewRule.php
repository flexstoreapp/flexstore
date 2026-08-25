<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Review;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

final class UniqueReviewRule implements DataAwareRule, ValidationRule
{
    /**
     * @var array{product_id?: int, user_id?: int}
     */
    private array $data;

    /**
     * @param  array{product_id?: int, user_id?: int}  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! isset($this->data['product_id']) || ! isset($this->data['user_id'])) {
            return;
        }

        $exists = Review::query()
            ->where('product_id', $this->data['product_id'])
            ->where('user_id', $this->data['user_id'])
            ->exists();

        if ($exists) {
            $fail(__('You have already reviewed this product.'));
        }
    }
}
