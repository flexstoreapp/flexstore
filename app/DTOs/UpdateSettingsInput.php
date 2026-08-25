<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UpdateSettingsInput
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public array $values,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(values: $data);
    }
}
