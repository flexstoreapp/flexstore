<?php

declare(strict_types=1);

namespace App\Utilities;

/**
 * Resolves a storefront link into a target an API client can act on, so a native
 * client can navigate without parsing storefront paths itself.
 */
final readonly class StorefrontLinkTarget
{
    /**
     * @return array<string, mixed>|null
     */
    public static function for(mixed $link): ?array
    {
        if (! is_string($link) || mb_trim($link) === '') {
            return null;
        }

        $link = mb_trim($link);

        if (preg_match('#^https?://#i', $link) === 1) {
            return ['type' => 'external', 'url' => $link];
        }

        $path = mb_trim((string) parse_url($link, PHP_URL_PATH), '/');
        $segments = $path === '' ? [] : explode('/', $path);

        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);

        return self::resolve($segments, $query) ?? ['type' => 'url', 'url' => $link];
    }

    /**
     * @param  list<string>  $segments
     * @param  array<int|string, array<mixed>|string>  $query
     * @return array<string, mixed>|null
     */
    private static function resolve(array $segments, array $query): ?array
    {
        $first = $segments[0] ?? '';
        $handle = $segments[1] ?? null;
        $depth = count($segments);

        return match (true) {
            $depth === 0 => ['type' => 'home'],
            $first === 'shop' && $depth === 1 => array_filter([
                'type' => 'shop',
                'query' => $query === [] ? null : $query,
            ], fn (mixed $value): bool => $value !== null),
            $first === 'search' && $depth === 1 => array_filter([
                'type' => 'search',
                'query' => isset($query['query']) && is_string($query['query']) ? $query['query'] : null,
            ], fn (mixed $value): bool => $value !== null),
            $first === 'categories' && $depth === 1 => ['type' => 'categories'],
            $first === 'categories' && $depth === 2 => ['type' => 'category', 'handle' => $handle],
            $first === 'brands' && $depth === 1 => ['type' => 'brands'],
            $first === 'brands' && $depth === 2 => ['type' => 'brand', 'handle' => $handle],
            $first === 'products' && $depth === 2 => ['type' => 'product', 'handle' => $handle],
            $first === 'flash-sales' && $depth === 1 => ['type' => 'flash_sales'],
            $first === 'flash-sales' && $depth === 2 => ['type' => 'flash_sale', 'handle' => $handle],
            $first === 'blog' && $depth === 1 => ['type' => 'blog'],
            $first === 'blog' && $depth === 2 => ['type' => 'post', 'handle' => $handle],
            $first === 'cart' && $depth === 1 => ['type' => 'cart'],
            $first === 'checkout' && $depth === 1 => ['type' => 'checkout'],
            $first === 'policies' && $depth === 2 => ['type' => 'policy', 'policy' => $handle],
            $first === 'account' => ['type' => 'account'],
            default => null,
        };
    }
}
