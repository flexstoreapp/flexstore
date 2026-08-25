<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\CategoryHierarchyException;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

final readonly class ReorderCategoryAction
{
    public function handle(Category $category, ?int $newParentId, int $position): Category
    {
        return DB::transaction(function () use ($category, $newParentId, $position): Category {
            if ($newParentId !== null) {
                /** @var Category $newParent */
                $newParent = Category::query()->findOrFail($newParentId);

                if ($category->id === $newParent->id || $category->isAncestorOf($newParent)) {
                    throw new CategoryHierarchyException();
                }
            }

            $category->update(['parent_id' => $newParentId]);

            $this->reindexSiblings($category, $position);

            return $category->refresh();
        });
    }

    private function reindexSiblings(Category $category, int $targetPosition): void
    {
        $siblings = Category::query()
            ->where('parent_id', $category->parent_id)
            ->where('id', '!=', $category->id)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $targetPosition = min($targetPosition, count($siblings));

        array_splice($siblings, $targetPosition, 0, [$category->id]);

        foreach ($siblings as $index => $id) {
            Category::query()
                ->where('id', $id)
                ->update(['sort_order' => $index]);
        }
    }
}
