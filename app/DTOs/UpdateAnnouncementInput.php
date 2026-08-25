<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UpdateAnnouncementInput
{
    /**
     * @param  array<string, string>|string|null  $content
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public array|string|null $content,
        public ?string $url,
        public ?bool $isActive,
        public ?string $startsAt,
        public ?string $endsAt,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $provided = [];
        foreach (['content', 'url', 'is_active', 'starts_at', 'ends_at'] as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        return new self(
            content: $data['content'] ?? null,
            url: isset($data['url']) ? (string) $data['url'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            startsAt: isset($data['starts_at']) ? (string) $data['starts_at'] : null,
            endsAt: isset($data['ends_at']) ? (string) $data['ends_at'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
