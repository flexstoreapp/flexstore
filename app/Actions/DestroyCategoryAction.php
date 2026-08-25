<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Category;

final readonly class DestroyCategoryAction
{
    public function handle(Category $category): bool
    {
        return $category->delete() ?? false;
    }
}
