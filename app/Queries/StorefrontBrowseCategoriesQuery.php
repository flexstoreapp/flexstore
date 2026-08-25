<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\SettingGroup;
use App\Models\Category;
use App\Models\Media;
use App\Models\Setting;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class StorefrontBrowseCategoriesQuery
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        $config = Setting::getByGroup(SettingGroup::Storefront)->get('storefront_header_browse_categories', []);
        $config = is_array($config) ? $config : [];

        if ($config === []) {
            return [];
        }

        $categoryIds = array_values(array_filter(array_column($config, 'category_id')));
        $mediaIds = array_values(array_filter(array_column($config, 'featured_image_id')));

        $categories = $this->loadCategories($categoryIds);
        $media = Media::query()->whereKey($mediaIds)->select(Media::displayColumns())->get()->keyBy('id');

        return collect($config)
            ->map(function (array $item) use ($categories, $media): ?array {
                $category = $categories->get($item['category_id'] ?? null);

                if (! $category instanceof Category) {
                    return null;
                }

                $isMegaMenu = (bool) ($item['is_mega_menu'] ?? false);
                $featuredImage = isset($item['featured_image_id']) ? $media->get($item['featured_image_id']) : null;

                return $this->toMenuItem($category, $isMegaMenu, $this->featured($item, $isMegaMenu, $featuredImage));
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{title: string, url: string|null, image: string|null}|null
     */
    private function featured(array $item, bool $isMegaMenu, ?Media $featuredImage): ?array
    {
        if (! $isMegaMenu || ! $featuredImage instanceof Media) {
            return null;
        }

        $url = $item['featured_url'] ?? null;

        return [
            'title' => $this->resolveTranslation($item['featured_title'] ?? null) ?? '',
            'url' => is_string($url) && $url !== '' ? $url : null,
            'image' => $featuredImage->thumbnail_url ?? $featuredImage->url,
        ];
    }

    /**
     * @param  array<int, int>  $categoryIds
     * @return Collection<int, Category>
     */
    private function loadCategories(array $categoryIds): Collection
    {
        $active = fn (Builder $query): Builder => $query
            ->where('is_active', true)
            ->select(['id', 'parent_id', 'name', 'url_handle']);

        return Category::query()
            ->whereKey($categoryIds)
            ->where('is_active', true)
            ->select(['id', 'name', 'url_handle'])
            ->with(['children' => fn (Builder $q) => $active($q)
                ->with(['children' => fn (Builder $q): Builder => $active($q)])])
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  array{title: string, url: string|null, image: string|null}|null  $featured
     * @return array<string, mixed>
     */
    private function toMenuItem(Category $category, bool $isMegaMenu, ?array $featured): array
    {
        $children = $category->relationLoaded('children') ? $category->children : new Collection();

        $item = [
            'label' => $category->name,
            'url' => route('categories.products.show', $category->url_handle),
            'is_mega_menu' => $isMegaMenu,
            'children' => $children->map(fn (Category $child): array => $this->toChildMenuItem($child))->all(),
        ];

        if ($featured !== null) {
            $item['featured'] = $featured;
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function toChildMenuItem(Category $category): array
    {
        $children = $category->relationLoaded('children') ? $category->children : new Collection();

        return [
            'label' => $category->name,
            'url' => route('categories.products.show', $category->url_handle),
            'is_mega_menu' => false,
            'children' => $children->map(fn (Category $child): array => $this->toChildMenuItem($child))->all(),
        ];
    }

    private function resolveTranslation(mixed $map): ?string
    {
        if (! is_array($map) || $map === []) {
            return null;
        }

        $value = $map[app()->getLocale()] ?? $map[(string) config('app.locale')] ?? reset($map);

        return is_string($value) ? $value : null;
    }
}
