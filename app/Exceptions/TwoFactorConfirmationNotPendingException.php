<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class TwoFactorConfirmationNotPendingException extends Exception
{
    protected $message = 'Two-factor authentication confirmation is not pending.';

    public function render(): void
    {
        abort(403, $this->getMessage());
    }
}
