<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class TwoFactorSetupNotStartedException extends Exception
{
    protected $message = 'Two-factor authentication setup has not been started.';

    public function render(): void
    {
        abort(403, $this->getMessage());
    }
}
