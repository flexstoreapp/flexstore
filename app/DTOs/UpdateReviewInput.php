<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UpdateReviewInput
{
    /**
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?int $rating,
        public ?string $title,
        public ?string $content,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $provided = [];
        foreach (['rating', 'title', 'content'] as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        return new self(
            rating: isset($data['rating']) ? (int) $data['rating'] : null,
            title: isset($data['title']) ? (string) $data['title'] : null,
            content: isset($data['content']) ? (string) $data['content'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
