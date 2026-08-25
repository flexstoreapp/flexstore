<?php

declare(strict_types=1);

namespace App\Installer\Contracts;

interface EnvWriter
{
    /**
     * @param  array<string, string>  $values
     */
    public function write(array $values): void;
}
