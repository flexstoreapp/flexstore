<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\StorefrontSectionType;

final readonly class StoreStorefrontSectionInput
{
    /**
     * @param  array<string, string>|string  $title
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public StorefrontSectionType $type,
        public array|string $title,
        public array $settings,
        public bool $isActive,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $type = $data['type'] instanceof StorefrontSectionType
            ? $data['type']
            : StorefrontSectionType::from((string) $data['type']);

        return new self(
            type: $type,
            title: $data['title'],
            settings: isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : [],
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }
}
