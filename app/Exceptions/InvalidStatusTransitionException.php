<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class InvalidStatusTransitionException extends Exception
{
    public static function make(string $statusType, string $from, string $to): static
    {
        return new self("Invalid {$statusType} status transition from '{$from}' to '{$to}'.");
    }

    public function render(): void
    {
        abort(409, $this->getMessage());
    }
}
