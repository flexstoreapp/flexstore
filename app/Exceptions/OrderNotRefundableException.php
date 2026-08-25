<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class OrderNotRefundableException extends Exception
{
    public function __construct()
    {
        parent::__construct(__('This order cannot be refunded.'));
    }

    public function render(): void
    {
        abort(410, $this->getMessage());
    }
}
