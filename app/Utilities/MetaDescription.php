<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Support\Str;

final readonly class MetaDescription
{
    public static function resolve(string $seoDescription, string $description, int $limit = 160): string
    {
        if ($seoDescription !== '') {
            return $seoDescription;
        }

        $text = mb_trim((string) preg_replace('/\s+/', ' ', strip_tags($description)));

        return Str::limit($text, $limit, preserveWords: true);
    }
}
