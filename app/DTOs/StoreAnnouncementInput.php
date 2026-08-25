<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class StoreAnnouncementInput
{
    /**
     * @param  array<string, string>|string  $content
     */
    public function __construct(
        public array|string $content,
        public ?string $url,
        public ?bool $isActive,
        public ?string $startsAt,
        public ?string $endsAt,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            content: $data['content'],
            url: isset($data['url']) ? (string) $data['url'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            startsAt: isset($data['starts_at']) ? (string) $data['starts_at'] : null,
            endsAt: isset($data['ends_at']) ? (string) $data['ends_at'] : null,
        );
    }
}
