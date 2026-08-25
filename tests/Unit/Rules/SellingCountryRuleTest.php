<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Rules\SellingCountryRule;

covers(SellingCountryRule::class);

uses()->group('rules', 'address');

function validateSellingCountry(mixed $value): bool
{
    $failed = false;
    (new SellingCountryRule)->validate('country_code', $value, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

test('passes for any real country when no countries are configured', function () {
    Setting::setValue('selling_countries', []);

    expect(validateSellingCountry('US'))->toBeFalse()
        ->and(validateSellingCountry('BD'))->toBeFalse();
});

test('fails for a country outside the configured list', function () {
    Setting::setValue('selling_countries', ['BD']);

    expect(validateSellingCountry('BD'))->toBeFalse()
        ->and(validateSellingCountry('US'))->toBeTrue();
});

test('fails for a value that is not a country code', function () {
    Setting::setValue('selling_countries', []);

    expect(validateSellingCountry('ZZ'))->toBeTrue()
        ->and(validateSellingCountry(null))->toBeTrue()
        ->and(validateSellingCountry(['US']))->toBeTrue();
});
