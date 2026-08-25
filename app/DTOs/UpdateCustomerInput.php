<?php

declare(strict_types=1);

namespace App\DTOs;

use SensitiveParameter;

final readonly class UpdateCustomerInput
{
    /**
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?string $name,
        public ?string $email,
        #[SensitiveParameter] public ?string $password,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $keys = ['name', 'email', 'password'];
        $provided = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        return new self(
            name: isset($data['name']) ? (string) $data['name'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            password: isset($data['password']) && $data['password'] !== '' ? (string) $data['password'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
