<?php

declare(strict_types=1);

namespace App\DTOs;

use SensitiveParameter;

final readonly class TwoFactorChallengeInput
{
    public function __construct(
        #[SensitiveParameter] public ?string $code,
        #[SensitiveParameter] public ?string $recoveryCode,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(#[SensitiveParameter] array $data): self
    {
        return new self(
            code: isset($data['code']) ? (string) $data['code'] : null,
            recoveryCode: isset($data['recovery_code']) ? (string) $data['recovery_code'] : null,
        );
    }
}
