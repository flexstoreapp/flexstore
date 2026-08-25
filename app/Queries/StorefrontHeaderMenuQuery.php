<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Models\Media;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

final readonly class StorefrontHeaderMenuQuery
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
            ->forLocation(MenuLocation::Header)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'featuredImage:' . Media::displaySelect(),
                'brand:id,url_handle',
                'category:id,url_handle',
                'children' => $activeChildren,
                'children.brand:id,url_handle',
                'children.category:id,url_handle',
                'children.children' => $activeChildren,
                'children.children.brand:id,url_handle',
                'children.children.category:id,url_handle',
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

        $item = [
            'label' => $menuItem->label,
            'url' => $this->resolveUrl($menuItem),
            'target' => $menuItem->target,
            'is_mega_menu' => $menuItem->is_mega_menu,
            'children' => $children->map(fn (MenuItem $child): array => $this->toMenuItem($child))->all(),
        ];

        $featured = $this->featured($menuItem);

        if ($featured !== null) {
            $item['featured'] = $featured;
        }

        return $item;
    }

    /**
     * @return array{title: string, url: string|null, image: string|null}|null
     */
    private function featured(MenuItem $menuItem): ?array
    {
        if (! $menuItem->is_mega_menu || ! $menuItem->relationLoaded('featuredImage')) {
            return null;
        }

        $image = $menuItem->featuredImage;

        if (! $image instanceof Media) {
            return null;
        }

        $url = $menuItem->featured_url;

        return [
            'title' => $menuItem->featured_title,
            'url' => is_string($url) && $url !== '' ? $url : null,
            'image' => $image->thumbnail_url ?? $image->url,
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
