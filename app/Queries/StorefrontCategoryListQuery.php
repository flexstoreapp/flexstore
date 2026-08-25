<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

final readonly class StorefrontCategoryListQuery
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        /** @var Collection<int, Category> $categories */
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'parent_id', 'url_handle', 'name']);

        $directCounts = Product::query()
            ->where('is_active', true)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        $byParent = $categories->groupBy('parent_id');

        $subtreeCount = function (int $id) use (&$subtreeCount, $byParent, $directCounts): int {
            $total = (int) ($directCounts[$id] ?? 0);

            foreach ($byParent->get($id, new Collection) as $child) {
                $total += $subtreeCount($child->id);
            }

            return $total;
        };

        return $categories
            ->whereNull('parent_id')
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'url_handle' => $category->url_handle,
                'name' => $category->getTranslations('name'),
                'product_count' => $subtreeCount($category->id),
                'children' => $byParent
                    ->get($category->id, new Collection)
                    ->map(fn (Category $child): array => [
                        'id' => $child->id,
                        'url_handle' => $child->url_handle,
                        'name' => $child->getTranslations('name'),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
