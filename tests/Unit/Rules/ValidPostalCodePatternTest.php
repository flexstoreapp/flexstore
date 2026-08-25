<?php

declare(strict_types=1);

use App\Rules\ValidPostalCodePattern;

covers(ValidPostalCodePattern::class);

uses()->group('rules');

function validatePostalCodePattern(mixed $value): bool
{
    $failed = false;

    (new ValidPostalCodePattern)->validate('postal_codes.0', $value, function () use (&$failed): void {
        $failed = true;
    });

    return ! $failed;
}

test('accepts exact postal_codes', function (): void {
    expect(validatePostalCodePattern('90210'))->toBeTrue();
    expect(validatePostalCodePattern('SW1A 1AA'))->toBeTrue();
});

test('accepts hyphenated literal postal_codes', function (): void {
    expect(validatePostalCodePattern('100-0001'))->toBeTrue();   // Japan
    expect(validatePostalCodePattern('90210-1234'))->toBeTrue(); // US ZIP+4
});

test('accepts prefix wildcards', function (): void {
    expect(validatePostalCodePattern('902*'))->toBeTrue();
    expect(validatePostalCodePattern('SW1*'))->toBeTrue();
});

test('rejects a bare wildcard with no prefix', function (): void {
    expect(validatePostalCodePattern('*'))->toBeFalse();
});

test('rejects a wildcard that is not at the end', function (): void {
    expect(validatePostalCodePattern('9*0'))->toBeFalse();
});

test('accepts a valid numeric range', function (): void {
    expect(validatePostalCodePattern('90210..90299'))->toBeTrue();
});

test('rejects a reversed numeric range', function (): void {
    expect(validatePostalCodePattern('90299..90210'))->toBeFalse();
});

test('rejects a malformed range', function (): void {
    expect(validatePostalCodePattern('90210..'))->toBeFalse();
    expect(validatePostalCodePattern('..90299'))->toBeFalse();
    expect(validatePostalCodePattern('90210...90299'))->toBeFalse();
});

test('rejects empty and non-string values', function (): void {
    expect(validatePostalCodePattern(''))->toBeFalse();
    expect(validatePostalCodePattern('   '))->toBeFalse();
    expect(validatePostalCodePattern(123))->toBeFalse();
});

test('rejects unsupported characters', function (): void {
    expect(validatePostalCodePattern('90210!'))->toBeFalse();
    expect(validatePostalCodePattern('90/210'))->toBeFalse();
});
