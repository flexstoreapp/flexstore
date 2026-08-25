<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class StoreBrandInput
{
    /**
     * @param  array<string, string>|string  $name
     * @param  array<string, string>|string|null  $description
     * @param  array<string, string>|string|null  $seoTitle
     * @param  array<string, string>|string|null  $seoDescription
     */
    public function __construct(
        public array|string $name,
        public ?string $urlHandle,
        public array|string|null $description,
        public array|string|null $seoTitle,
        public array|string|null $seoDescription,
        public ?int $imageId,
        public bool $isActive,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            urlHandle: isset($data['url_handle']) ? (string) $data['url_handle'] : null,
            description: $data['description'] ?? null,
            seoTitle: $data['seo_title'] ?? null,
            seoDescription: $data['seo_description'] ?? null,
            imageId: isset($data['image_id']) && $data['image_id'] !== '' ? (int) $data['image_id'] : null,
            isActive: (bool) $data['is_active'],
        );
    }
}
