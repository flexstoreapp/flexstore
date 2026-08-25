<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateMenuItemInput;
use App\Models\MenuItem;

final readonly class UpdateMenuItemAction
{
    public function handle(MenuItem $menuItem, UpdateMenuItemInput $input): MenuItem
    {
        $menuItem->update([
            'location' => $input->has('location') ? $input->location : $menuItem->location,
            'label' => $input->has('label') ? $input->label : $menuItem->label,
            'link_type' => $input->has('link_type') ? $input->linkType : $menuItem->link_type,
            'brand_id' => $input->has('brand_id') ? $input->brandId : $menuItem->brand_id,
            'category_id' => $input->has('category_id') ? $input->categoryId : $menuItem->category_id,
            'url' => $input->has('url') ? $input->url : $menuItem->url,
            'page' => $input->has('page') ? $input->page : $menuItem->page,
            'target' => $input->has('target') ? $input->target : $menuItem->target,
            'parent_id' => $input->has('parent_id') ? $input->parentId : $menuItem->parent_id,
            'sort_order' => $input->has('sort_order') ? $input->sortOrder : $menuItem->sort_order,
            'is_mega_menu' => $input->has('is_mega_menu') ? $input->isMegaMenu : $menuItem->is_mega_menu,
            'is_active' => $input->has('is_active') ? $input->isActive : $menuItem->is_active,
            'featured_image_id' => $input->has('featured_image_id') ? $input->featuredImageId : $menuItem->featured_image_id,
            'featured_title' => $input->has('featured_title') ? $input->featuredTitle : $menuItem->featured_title,
            'featured_url' => $input->has('featured_url') ? $input->featuredUrl : $menuItem->featured_url,
        ]);

        return $menuItem;
    }
}
