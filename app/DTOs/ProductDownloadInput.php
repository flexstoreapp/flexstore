<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ProductDownloadInput
{
    public function __construct(
        public ?string $id,
        public ?string $variantId,
        public string $name,
        public int $mediaId,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            variantId: isset($data['variant_id']) && $data['variant_id'] !== '' ? (string) $data['variant_id'] : null,
            name: (string) ($data['name'] ?? ''),
            mediaId: (int) ($data['media_id'] ?? 0),
        );
    }
}
