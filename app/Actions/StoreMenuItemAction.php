<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreMenuItemInput;
use App\Models\MenuItem;

final readonly class StoreMenuItemAction
{
    public function handle(StoreMenuItemInput $input): MenuItem
    {
        $maxSortOrder = MenuItem::query()
            ->where('location', $input->location)
            ->where('parent_id', $input->parentId)
            ->max('sort_order') ?? -1;

        return MenuItem::query()->create([
            'location' => $input->location,
            'label' => $input->label,
            'link_type' => $input->linkType,
            'brand_id' => $input->brandId,
            'category_id' => $input->categoryId,
            'url' => $input->url,
            'page' => $input->page,
            'target' => $input->target,
            'parent_id' => $input->parentId,
            'sort_order' => $maxSortOrder + 1,
            'is_mega_menu' => $input->isMegaMenu,
            'is_active' => $input->isActive,
            'featured_image_id' => $input->featuredImageId,
            'featured_title' => $input->featuredTitle,
            'featured_url' => $input->featuredUrl,
        ]);
    }
}
