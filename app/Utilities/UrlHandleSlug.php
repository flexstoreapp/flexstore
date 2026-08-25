<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Support\Str;

final readonly class UrlHandleSlug
{
    public static function make(string $value, string $prefix): string
    {
        $slug = Str::slug(Str::transliterate($value));

        if ($slug !== '') {
            return $slug;
        }

        return $prefix . '-' . mb_substr(hash('xxh128', $value), 0, 8);
    }
}
