<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreCategoryInput;
use App\Models\Category;

final readonly class StoreCategoryAction
{
    public function handle(StoreCategoryInput $input): Category
    {
        $category = new Category([
            'name' => $input->name,
            'url_handle' => $input->urlHandle,
            'description' => $input->description,
            'seo_title' => $input->seoTitle,
            'seo_description' => $input->seoDescription,
            'is_active' => $input->isActive,
            'parent_id' => $input->parentId,
            'sort_order' => $this->nextSortOrder($input->parentId),
        ]);

        $category->save();

        return $category;
    }

    private function nextSortOrder(?int $parentId): int
    {
        $max = Category::query()
            ->where('parent_id', $parentId)
            ->max('sort_order');

        return $max !== null ? $max + 1 : 0;
    }
}
