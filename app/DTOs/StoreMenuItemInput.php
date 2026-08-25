<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Enums\MenuPage;

final readonly class StoreMenuItemInput
{
    /**
     * @param  array<string, string>|string  $label
     * @param  array<string, string>|string|null  $featuredTitle
     */
    public function __construct(
        public MenuLocation $location,
        public array|string $label,
        public MenuItemLinkType $linkType,
        public ?int $brandId,
        public ?int $categoryId,
        public ?string $url,
        public ?MenuPage $page,
        public string $target,
        public ?int $parentId,
        public bool $isMegaMenu,
        public ?int $featuredImageId,
        public array|string|null $featuredTitle,
        public ?string $featuredUrl,
        public bool $isActive,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $location = $data['location'] instanceof MenuLocation ? $data['location'] : MenuLocation::from((string) $data['location']);
        $linkType = $data['link_type'] instanceof MenuItemLinkType ? $data['link_type'] : MenuItemLinkType::from((string) $data['link_type']);

        $page = null;
        if (array_key_exists('page', $data) && $data['page'] !== null) {
            $page = $data['page'] instanceof MenuPage ? $data['page'] : MenuPage::from((string) $data['page']);
        }

        return new self(
            location: $location,
            label: $data['label'],
            linkType: $linkType,
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            url: isset($data['url']) ? (string) $data['url'] : null,
            page: $page,
            target: isset($data['target']) ? (string) $data['target'] : '_self',
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            isMegaMenu: (bool) ($data['is_mega_menu'] ?? false),
            featuredImageId: isset($data['featured_image_id']) && $data['featured_image_id'] !== '' ? (int) $data['featured_image_id'] : null,
            featuredTitle: $data['featured_title'] ?? null,
            featuredUrl: isset($data['featured_url']) ? (string) $data['featured_url'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }
}
