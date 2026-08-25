<?php

declare(strict_types=1);

namespace App\DTOs;

use SensitiveParameter;

final readonly class StoreCustomerInput
{
    public function __construct(
        public string $name,
        public string $email,
        #[SensitiveParameter] public string $password,
        public ?string $emailVerifiedAt = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            password: (string) $data['password'],
            emailVerifiedAt: isset($data['email_verified_at']) ? (string) $data['email_verified_at'] : null,
        );
    }
}
