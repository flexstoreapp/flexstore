<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class NotSelfParent implements ValidationRule
{
    public function __construct(
        private Category $category
    ) {
    }

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        if ((int) $value === $this->category->id) {
            $fail(__('A category cannot be its own parent.'));
        }
    }
}
