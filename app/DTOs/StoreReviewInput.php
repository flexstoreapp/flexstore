<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class StoreReviewInput
{
    public function __construct(
        public int $productId,
        public int $userId,
        public int $rating,
        public ?string $title,
        public string $content,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            userId: (int) $data['user_id'],
            rating: (int) $data['rating'],
            title: isset($data['title']) ? (string) $data['title'] : null,
            content: (string) $data['content'],
        );
    }
}
