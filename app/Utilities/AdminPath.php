<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class AdminPath
{
    public const string DEFAULT = 'admin';

    /**
     * @var list<string>
     */
    public const array RESERVED = [
        'account', 'address-field-rules', 'brands', 'cart', 'categories', 'checkout',
        'compare', 'currency', 'discount', 'downloads', 'feeds', 'flash-sales', 'install',
        'locale', 'newsletter', 'pay', 'policies', 'products', 'search', 'storage',
        'track-order', 'translations', 'up', 'webhooks', 'wishlist',
    ];

    public static function prefix(): string
    {
        $configured = mb_trim((string) config('admin.prefix'), '/');

        if ($configured === '') {
            return self::DEFAULT;
        }

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9\-_]*$/', $configured) !== 1) {
            throw new InvalidArgumentException(
                "Invalid ADMIN_PREFIX [{$configured}]. Use letters, numbers, hyphens and underscores only, as a single path segment."
            );
        }

        if (in_array(mb_strtolower($configured), self::RESERVED, true)) {
            throw new InvalidArgumentException(
                "ADMIN_PREFIX [{$configured}] is reserved by the storefront. Choose a different prefix."
            );
        }

        return $configured;
    }

    public static function matches(Request $request): bool
    {
        $route = $request->route();
        $name = $route?->getName();

        if ($name !== null) {
            return str_starts_with($name, 'admin.');
        }

        return $request->is(self::prefix(), self::prefix() . '/*');
    }
}
