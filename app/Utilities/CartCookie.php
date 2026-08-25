<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Http\Request;

final readonly class CartCookie
{
    public const string NAME = 'cart_id';

    public static function from(Request $request): ?string
    {
        $value = $request->cookie(self::NAME);

        return is_string($value) ? $value : null;
    }
}
