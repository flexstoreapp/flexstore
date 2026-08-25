<?php

declare(strict_types=1);

namespace App\Utilities;

final readonly class LikePattern
{
    public const string ESCAPE_CLAUSE = "escape '!'";

    public static function escape(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
