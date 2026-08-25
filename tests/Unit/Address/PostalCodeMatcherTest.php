<?php

declare(strict_types=1);

use App\Address\PostalCodeMatcher;

covers(PostalCodeMatcher::class);

uses()->group('address');

test('matches an exact postal_code', function (): void {
    expect(PostalCodeMatcher::matches('90210', ['90210']))->toBeTrue();
    expect(PostalCodeMatcher::matches('90211', ['90210']))->toBeFalse();
});

test('matches a hyphenated literal postal_code', function (): void {
    expect(PostalCodeMatcher::matches('100-0001', ['100-0001']))->toBeTrue();
    expect(PostalCodeMatcher::matches('100-0002', ['100-0001']))->toBeFalse();
    expect(PostalCodeMatcher::matches('90210-1234', ['90210-1234']))->toBeTrue();
});

test('exact matching is case-insensitive and ignores surrounding whitespace', function (): void {
    expect(PostalCodeMatcher::matches(' sw1a 1aa ', ['SW1A 1AA']))->toBeTrue();
    expect(PostalCodeMatcher::matches('SW1A 1AA', [' sw1a 1aa ']))->toBeTrue();
});

test('matches a prefix wildcard', function (): void {
    expect(PostalCodeMatcher::matches('90210', ['902*']))->toBeTrue();
    expect(PostalCodeMatcher::matches('90299', ['902*']))->toBeTrue();
    expect(PostalCodeMatcher::matches('91000', ['902*']))->toBeFalse();
});

test('wildcard works with alphanumeric postal_codes', function (): void {
    expect(PostalCodeMatcher::matches('SW1A 1AA', ['SW1*']))->toBeTrue();
    expect(PostalCodeMatcher::matches('SW2A 1AA', ['SW1*']))->toBeFalse();
});

test('matches an inclusive numeric range', function (): void {
    expect(PostalCodeMatcher::matches('90210', ['90210..90299']))->toBeTrue();
    expect(PostalCodeMatcher::matches('90299', ['90210..90299']))->toBeTrue();
    expect(PostalCodeMatcher::matches('90250', ['90210..90299']))->toBeTrue();
    expect(PostalCodeMatcher::matches('90209', ['90210..90299']))->toBeFalse();
    expect(PostalCodeMatcher::matches('90300', ['90210..90299']))->toBeFalse();
});

test('a hyphen is never treated as a range separator', function (): void {
    expect(PostalCodeMatcher::matches('90250', ['90210-90299']))->toBeFalse();
    expect(PostalCodeMatcher::matches('90210-90299', ['90210-90299']))->toBeTrue();
});

test('a range never matches a non-numeric postal_code', function (): void {
    expect(PostalCodeMatcher::matches('SW1A 1AA', ['10000..99999']))->toBeFalse();
});

test('matches when any pattern in the list matches', function (): void {
    $patterns = ['10001', '902*', '60601..60699'];

    expect(PostalCodeMatcher::matches('10001', $patterns))->toBeTrue();
    expect(PostalCodeMatcher::matches('90250', $patterns))->toBeTrue();
    expect(PostalCodeMatcher::matches('60650', $patterns))->toBeTrue();
    expect(PostalCodeMatcher::matches('30303', $patterns))->toBeFalse();
});

test('an empty postal_code never matches', function (): void {
    expect(PostalCodeMatcher::matches('', ['90210', '902*', '90210..90299']))->toBeFalse();
    expect(PostalCodeMatcher::matches('   ', ['902*']))->toBeFalse();
});

test('returns false when there are no patterns', function (): void {
    expect(PostalCodeMatcher::matches('90210', []))->toBeFalse();
});
