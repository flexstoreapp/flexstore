<?php

declare(strict_types=1);

use App\Address\SellingCountries;
use App\Enums\Country;
use App\Models\Setting;

covers(SellingCountries::class);

uses()->group('address');

test('supports every country when no countries are configured', function (): void {
    Setting::setValue('selling_countries', []);

    expect(SellingCountries::codes())->toBe(array_values(Country::codes()))
        ->and(SellingCountries::supports('BD'))->toBeTrue()
        ->and(SellingCountries::supports('US'))->toBeTrue()
        ->and(SellingCountries::supports('ZZ'))->toBeFalse();
});

test('restricts to the configured countries', function (): void {
    Setting::setValue('selling_countries', ['BD', 'US']);

    expect(SellingCountries::codes())->toBe(['BD', 'US'])
        ->and(SellingCountries::supports('BD'))->toBeTrue()
        ->and(SellingCountries::supports('CA'))->toBeFalse();
});

test('ignores configured codes that are not real countries', function (): void {
    Setting::setValue('selling_countries', ['BD', 'ZZ']);

    expect(SellingCountries::codes())->toBe(['BD'])
        ->and(SellingCountries::supports('ZZ'))->toBeFalse();
});
