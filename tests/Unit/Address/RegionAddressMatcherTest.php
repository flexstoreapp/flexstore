<?php

declare(strict_types=1);

use App\Address\RegionAddressMatcher;
use App\DTOs\AddressLocation;
use App\Models\Region;

covers(RegionAddressMatcher::class);

uses()->group('address');

function matcherRegion(array $attributes): Region
{
    return Region::factory()->make([
        'countries' => [],
        'states' => [],
        'postal_codes' => [],
        ...$attributes,
    ]);
}

test('matches a region with no constraints', function (): void {
    $region = matcherRegion([]);
    $address = new AddressLocation(countryCode: 'US', state: 'CA', postalCode: '90210');

    expect(RegionAddressMatcher::matches($region, $address))->toBeTrue();
});

test('matches and rejects on country', function (): void {
    $region = matcherRegion(['countries' => ['US']]);

    expect(RegionAddressMatcher::matches($region, new AddressLocation('US', '', '')))->toBeTrue()
        ->and(RegionAddressMatcher::matches($region, new AddressLocation('CA', '', '')))->toBeFalse();
});

test('matches and rejects on state', function (): void {
    $region = matcherRegion(['states' => ['NY']]);

    expect(RegionAddressMatcher::matches($region, new AddressLocation('US', 'NY', '')))->toBeTrue()
        ->and(RegionAddressMatcher::matches($region, new AddressLocation('US', 'CA', '')))->toBeFalse();
});

test('matches a postal code against a wildcard or range pattern', function (): void {
    $region = matcherRegion(['postal_codes' => ['902*', '60601..60699']]);

    expect(RegionAddressMatcher::matches($region, new AddressLocation('US', '', '90250')))->toBeTrue()
        ->and(RegionAddressMatcher::matches($region, new AddressLocation('US', '', '60650')))->toBeTrue()
        ->and(RegionAddressMatcher::matches($region, new AddressLocation('US', '', '30303')))->toBeFalse();
});

test('rejects when a postal-constrained region gets an empty postal code', function (): void {
    $region = matcherRegion(['postal_codes' => ['902*']]);

    expect(RegionAddressMatcher::matches($region, new AddressLocation('US', '', '')))->toBeFalse();
});

test('requires every present constraint to match', function (): void {
    $region = matcherRegion(['countries' => ['US'], 'states' => ['CA'], 'postal_codes' => ['902*']]);

    expect(RegionAddressMatcher::matches($region, new AddressLocation('US', 'CA', '90210')))->toBeTrue()
        ->and(RegionAddressMatcher::matches($region, new AddressLocation('US', 'NY', '90210')))->toBeFalse()
        ->and(RegionAddressMatcher::matches($region, new AddressLocation('CA', 'CA', '90210')))->toBeFalse();
});
