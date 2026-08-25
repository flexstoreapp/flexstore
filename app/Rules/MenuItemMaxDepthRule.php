<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\MenuItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class MenuItemMaxDepthRule implements ValidationRule
{
    public function __construct(
        private int $maxDepth = 3,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $parent = MenuItem::query()->find($value);

        if (! $parent instanceof MenuItem) {
            return;
        }

        $parentDepth = $this->getDepth($parent);

        if ($parentDepth >= $this->maxDepth) {
            $fail(__('The menu item cannot be nested more than :max_depth levels deep.', ['max_depth' => $this->maxDepth]));
        }
    }

    private function getDepth(MenuItem $menuItem): int
    {
        $depth = 1;
        $current = $menuItem;

        while ($current->parent_id !== null) {
            $depth++;
            $current = $current->parent;

            if ($current === null) {
                break;
            }
        }

        return $depth;
    }
}
