<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Support\Facades\DB;

final readonly class SubstringPosition
{
    /**
     * @param  literal-string  $haystack
     * @param  literal-string  $needle
     * @return literal-string
     */
    public static function expression(string $haystack, string $needle = '?'): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "strpos({$haystack}, {$needle})",
            default => "instr({$haystack}, {$needle})",
        };
    }
}
