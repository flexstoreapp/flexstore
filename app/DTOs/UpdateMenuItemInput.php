<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Enums\MenuPage;

final readonly class UpdateMenuItemInput
{
    /**
     * @param  array<string, string>|string|null  $label
     * @param  array<string, string>|string|null  $featuredTitle
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?MenuLocation $location,
        public array|string|null $label,
        public ?MenuItemLinkType $linkType,
        public ?int $brandId,
        public ?int $categoryId,
        public ?string $url,
        public ?MenuPage $page,
        public ?string $target,
        public ?int $parentId,
        public ?int $sortOrder,
        public ?bool $isMegaMenu,
        public ?int $featuredImageId,
        public array|string|null $featuredTitle,
        public ?string $featuredUrl,
        public ?bool $isActive,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $provided = [];
        $keys = ['location', 'label', 'link_type', 'brand_id', 'category_id', 'url', 'page', 'target', 'parent_id', 'sort_order', 'is_mega_menu', 'featured_image_id', 'featured_title', 'featured_url', 'is_active'];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        $location = null;
        if (array_key_exists('location', $data) && $data['location'] !== null) {
            $location = $data['location'] instanceof MenuLocation ? $data['location'] : MenuLocation::from((string) $data['location']);
        }

        $linkType = null;
        if (array_key_exists('link_type', $data) && $data['link_type'] !== null) {
            $linkType = $data['link_type'] instanceof MenuItemLinkType ? $data['link_type'] : MenuItemLinkType::from((string) $data['link_type']);
        }

        $page = null;
        if (array_key_exists('page', $data) && $data['page'] !== null) {
            $page = $data['page'] instanceof MenuPage ? $data['page'] : MenuPage::from((string) $data['page']);
        }

        return new self(
            location: $location,
            label: $data['label'] ?? null,
            linkType: $linkType,
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            url: isset($data['url']) ? (string) $data['url'] : null,
            page: $page,
            target: isset($data['target']) ? (string) $data['target'] : null,
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            sortOrder: isset($data['sort_order']) ? (int) $data['sort_order'] : null,
            isMegaMenu: isset($data['is_mega_menu']) ? (bool) $data['is_mega_menu'] : null,
            featuredImageId: isset($data['featured_image_id']) && $data['featured_image_id'] !== '' ? (int) $data['featured_image_id'] : null,
            featuredTitle: $data['featured_title'] ?? null,
            featuredUrl: isset($data['featured_url']) ? (string) $data['featured_url'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
