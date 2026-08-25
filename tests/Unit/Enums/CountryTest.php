<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Enums\Country;

covers(Country::class);

uses()->group('enums', 'country');

test('codes method returns all country codes', function () {
    $values = Country::codes();
    $caseValues = array_map(fn ($c) => $c->name, Country::cases());

    expect($values)->toBe($caseValues);
});

test('fromNameOrValue returns correct enum case for backing value', function () {
    expect(Country::fromNameOrValue('United States'))->toBe(Country::US);
    expect(Country::fromNameOrValue('Canada'))->toBe(Country::CA);
    expect(Country::fromNameOrValue('United Kingdom'))->toBe(Country::GB);
});

test('fromNameOrValue returns correct enum case for case name', function () {
    expect(Country::fromNameOrValue('US'))->toBe(Country::US);
    expect(Country::fromNameOrValue('CA'))->toBe(Country::CA);
    expect(Country::fromNameOrValue('GB'))->toBe(Country::GB);
});

test('fromNameOrValue returns null for invalid value', function () {
    expect(Country::fromNameOrValue('Invalid Country'))->toBeNull();
    expect(Country::fromNameOrValue(''))->toBeNull();
    expect(Country::fromNameOrValue('INVALID'))->toBeNull();
});
