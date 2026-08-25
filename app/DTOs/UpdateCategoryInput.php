<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UpdateCategoryInput
{
    /**
     * @param  array<string, string>|string|null  $name
     * @param  array<string, string>|string|null  $description
     * @param  array<string, string>|string|null  $seoTitle
     * @param  array<string, string>|string|null  $seoDescription
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public array|string|null $name,
        public ?string $urlHandle,
        public array|string|null $description,
        public array|string|null $seoTitle,
        public array|string|null $seoDescription,
        public ?int $parentId,
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
        foreach (['name', 'url_handle', 'description', 'seo_title', 'seo_description', 'parent_id', 'is_active'] as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        return new self(
            name: $data['name'] ?? null,
            urlHandle: isset($data['url_handle']) ? (string) $data['url_handle'] : null,
            description: $data['description'] ?? null,
            seoTitle: $data['seo_title'] ?? null,
            seoDescription: $data['seo_description'] ?? null,
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
