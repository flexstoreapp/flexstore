<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

final readonly class StorefrontFooterMenuQuery
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        $activeChildren = fn (Relation $query): Relation => $query
            ->where('is_active', true)
            ->orderBy('sort_order');

        return MenuItem::query()
            ->forLocation(MenuLocation::Footer)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'brand:id,url_handle',
                'category:id,url_handle',
                'children' => $activeChildren,
                'children.brand:id,url_handle',
                'children.category:id,url_handle',
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (MenuItem $menuItem): array => $this->toMenuItem($menuItem))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toMenuItem(MenuItem $menuItem): array
    {
        $children = $menuItem->relationLoaded('children') ? $menuItem->children : new Collection();

        return [
            'label' => $menuItem->label,
            'url' => $this->resolveUrl($menuItem),
            'target' => $menuItem->target,
            'children' => $children->map(fn (MenuItem $child): array => $this->toMenuItem($child))->all(),
        ];
    }

    private function resolveUrl(MenuItem $menuItem): string
    {
        if ($menuItem->link_type === MenuItemLinkType::Brand && $menuItem->brand) {
            return route('brands.products.show', $menuItem->brand);
        }

        if ($menuItem->link_type === MenuItemLinkType::Category && $menuItem->category) {
            return route('categories.products.show', $menuItem->category);
        }

        if ($menuItem->link_type === MenuItemLinkType::Page && $menuItem->page) {
            return $menuItem->page->url();
        }

        return $menuItem->url ?? '#';
    }
}
