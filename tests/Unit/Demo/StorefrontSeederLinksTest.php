<?php

declare(strict_types=1);

use App\Enums\ProductSortOption;
use App\Http\Requests\Storefront\ProductFilterRequest;

uses()->group('demo-data');

/**
 * Demo links are shown to every evaluator on a fresh install, so they must point
 * at filters the storefront actually honours.
 */
test('every seeded shop link uses filters the storefront supports', function (): void {
    $allowed = array_keys((new ProductFilterRequest())->rules());
    $sorts = array_map(fn (ProductSortOption $option): string => $option->value, ProductSortOption::cases());

    preg_match_all("#'/shop\?([^']+)'#", file_get_contents(database_path('seeders/StorefrontSeeder.php')), $matches);

    $problems = [];

    foreach (array_unique($matches[1]) as $queryString) {
        parse_str($queryString, $params);

        foreach ($params as $key => $value) {
            if (! in_array($key, $allowed, true)) {
                $problems[] = "unknown filter \"{$key}\" in /shop?{$queryString}";
            }

            if ($key === 'sort' && ! in_array($value, $sorts, true)) {
                $problems[] = "unknown sort \"{$value}\" in /shop?{$queryString}";
            }
        }
    }

    expect($problems)->toBeEmpty(implode("\n", $problems));
});
