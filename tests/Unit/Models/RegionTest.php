<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Region;
use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(Region::class);

uses()->group('models', 'region');

test('has factory', function () {
    expect(Region::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $region = new Region();
    $casts = $region->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('countries', 'array')
        ->toHaveKey('states', 'array')
        ->toHaveKey('postal_codes', 'array')
        ->toHaveKey('is_active', 'boolean');
});

test('has shippingRates relationship', function () {
    $region = Region::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'region_id' => $region->id,
    ]);

    expect($region->shippingRates)
        ->toBeInstanceOf(Collection::class)
        ->and($region->shippingRates->first()->id)->toBe($shippingRate->id);
});
