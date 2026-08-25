<?php

declare(strict_types=1);

namespace App\DTOs;

use SensitiveParameter;

final readonly class PasswordResetInput
{
    public function __construct(
        public string $email,
        public string $token,
        #[SensitiveParameter] public string $password,
        #[SensitiveParameter] public string $passwordConfirmation,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(#[SensitiveParameter] array $data): self
    {
        return new self(
            email: (string) $data['email'],
            token: (string) $data['token'],
            password: (string) $data['password'],
            passwordConfirmation: (string) ($data['password_confirmation'] ?? $data['password']),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toCredentials(): array
    {
        return [
            'email' => $this->email,
            'token' => $this->token,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
        ];
    }
}
