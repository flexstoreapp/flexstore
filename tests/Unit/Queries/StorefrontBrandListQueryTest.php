<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Queries\StorefrontBrandListQuery;

covers(StorefrontBrandListQuery::class);

uses()->group('queries', 'storefront');

test('returns only active brands sorted alphabetically', function () {
    Brand::factory()->active()->create(['name' => ['en' => 'Zenith']]);
    Brand::factory()->active()->create(['name' => ['en' => 'Alba']]);
    Brand::factory()->active()->create(['name' => ['en' => 'Meridian']]);
    Brand::factory()->inactive()->create(['name' => ['en' => 'Hidden']]);

    $result = app(StorefrontBrandListQuery::class)->execute();

    expect(array_column($result, 'name'))->toBe([
        ['en' => 'Alba'],
        ['en' => 'Meridian'],
        ['en' => 'Zenith'],
    ]);
});

test('exposes only the fields the brand tile renders', function () {
    Brand::factory()->active()->create();

    $result = app(StorefrontBrandListQuery::class)->execute();

    expect(array_keys($result[0]))->toBe(['id', 'name', 'url_handle', 'image']);
});
