<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Http\Request;

final readonly class ApiCartToken
{
    public const string HEADER = 'X-Cart-Token';

    public static function from(Request $request): ?string
    {
        $value = $request->header(self::HEADER);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
