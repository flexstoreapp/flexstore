<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class TwoFactorNotEnabledException extends Exception
{
    protected $message = 'Two-factor authentication is not enabled for this user.';

    public function render(): void
    {
        abort(403, $this->getMessage());
    }
}
