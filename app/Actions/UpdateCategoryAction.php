<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateCategoryInput;
use App\Exceptions\CategoryHierarchyException;
use App\Models\Category;

final readonly class UpdateCategoryAction
{
    public function handle(Category $category, UpdateCategoryInput $input): Category
    {
        $category->fill([
            'name' => $input->has('name') ? $input->name : $category->name,
            'url_handle' => $input->has('url_handle') ? $input->urlHandle : $category->url_handle,
            'description' => $input->has('description') ? $input->description : $category->description,
            'seo_title' => $input->has('seo_title') ? $input->seoTitle : $category->seo_title,
            'seo_description' => $input->has('seo_description') ? $input->seoDescription : $category->seo_description,
            'is_active' => $input->has('is_active') ? $input->isActive : $category->is_active,
        ]);

        $this->handleParentChange($category, $input);

        return $category;
    }

    private function handleParentChange(Category $category, UpdateCategoryInput $input): void
    {
        if (! $input->has('parent_id')) {
            $category->save();

            return;
        }

        $newParentId = $input->parentId;

        if ($newParentId === $category->parent_id) {
            $category->save();

            return;
        }

        if ($newParentId !== null) {
            $parent = Category::query()->findOrFail($newParentId);

            if ($category->isAncestorOf($parent)) {
                throw new CategoryHierarchyException();
            }
        }

        $category->update([
            'parent_id' => $newParentId,
            'sort_order' => $this->nextSortOrder($newParentId, $category->id),
        ]);
    }

    private function nextSortOrder(?int $parentId, int $excludeId): int
    {
        $max = Category::query()
            ->where('parent_id', $parentId)
            ->where('id', '!=', $excludeId)
            ->max('sort_order');

        return $max !== null ? $max + 1 : 0;
    }
}
