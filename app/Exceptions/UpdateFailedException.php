<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

final class UpdateFailedException extends Exception
{
    public function __construct(
        public readonly string $step,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
