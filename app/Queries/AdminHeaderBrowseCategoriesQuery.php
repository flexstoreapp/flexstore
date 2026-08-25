<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\SettingGroup;
use App\Models\Category;
use App\Models\Media;
use App\Models\Setting;

final readonly class AdminHeaderBrowseCategoriesQuery
{
    /**
     * @return array<int, array{category: Category, is_mega_menu: bool, featured_image: Media|null, featured_title: string|null, featured_url: string|null}>
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

        $categories = Category::query()->whereKey($categoryIds)->get(['id', 'name'])->keyBy('id');
        $media = Media::query()->whereKey($mediaIds)->select(Media::displayColumns())->get()->keyBy('id');

        return collect($config)
            ->map(fn (array $item): ?array => ($category = $categories->get($item['category_id'] ?? null)) === null ? null : [
                'category' => $category,
                'is_mega_menu' => (bool) ($item['is_mega_menu'] ?? false),
                'featured_image' => isset($item['featured_image_id']) ? $media->get($item['featured_image_id']) : null,
                'featured_title' => $this->resolveTranslation($item['featured_title'] ?? null),
                'featured_url' => $item['featured_url'] ?? null,
            ])
            ->filter()
            ->values()
            ->all();
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
