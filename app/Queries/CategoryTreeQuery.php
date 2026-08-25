<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final readonly class CategoryTreeQuery
{
    /**
     * @return Collection<int, Category>
     */
    public function execute(): Collection
    {
        $categories = Category::query()->orderBy('sort_order')->get();

        $grouped = $categories->groupBy('parent_id');

        $buildChildren = function (Collection $categories) use ($grouped, &$buildChildren): Collection {
            /** @var Category $category */
            foreach ($categories as $category) {
                $children = $grouped->get($category->id, new Collection);
                $category->setRelation('children', $buildChildren($children));
            }

            return $categories;
        };

        $roots = $categories->whereNull('parent_id')->values();

        /** @var Collection<int, Category> */
        return $buildChildren($roots);
    }
}
