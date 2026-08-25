<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class CategoryHierarchyException extends Exception
{
    protected $message = 'Cannot move a category under its own descendant.';

    public function render(): void
    {
        abort(400, $this->getMessage());
    }
}
