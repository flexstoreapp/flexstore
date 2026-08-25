<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\StorefrontSectionType;

final readonly class UpdateStorefrontSectionInput
{
    /**
     * @param  array<string, string>|string|null  $title
     * @param  array<string, mixed>|null  $settings
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?StorefrontSectionType $type,
        public array|string|null $title,
        public ?array $settings,
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
        foreach (['type', 'title', 'settings', 'is_active'] as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        $type = null;
        if (array_key_exists('type', $data) && $data['type'] !== null) {
            $type = $data['type'] instanceof StorefrontSectionType
                ? $data['type']
                : StorefrontSectionType::from((string) $data['type']);
        }

        return new self(
            type: $type,
            title: $data['title'] ?? null,
            settings: isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
