<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class NoCircularReference implements ValidationRule
{
    public function __construct(
        private Category $category
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $newParent = Category::query()->find($value);

        if (! $newParent instanceof Category) {
            return;
        }

        if ($this->category->isAncestorOf($newParent)) {
            $fail(__('Cannot move a category under its own descendant.'));
        }
    }
}
