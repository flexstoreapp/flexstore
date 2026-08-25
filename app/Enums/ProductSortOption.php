<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductSortOption: string
{
    case Relevance = 'relevance';
    case Latest = 'latest';
    case PriceLowHigh = 'price_asc';
    case PriceHighLow = 'price_desc';
    case NameAZ = 'name_asc';
    case NameZA = 'name_desc';

    public static function resolve(?string $requested, bool $hasSearch, string $default): string
    {
        $option = self::tryFrom((string) $requested);

        if ($option !== null) {
            return $option->value;
        }

        return $hasSearch ? self::Relevance->value : $default;
    }
}
