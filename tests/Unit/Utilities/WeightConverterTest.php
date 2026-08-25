<?php

declare(strict_types=1);

namespace Tests\Unit\Utilities;

use App\Enums\WeightUnit;
use App\Utilities\WeightConverter;

covers(WeightConverter::class);

uses()->group('utilities', 'weight');

test('converts kilograms to grams', function () {
    $result = WeightConverter::toGrams('1.5', WeightUnit::Kg);

    expect($result)->toBeString()
        ->and($result)->toBe('1500.00');
});

test('converts grams to grams (no conversion)', function () {
    $result = WeightConverter::toGrams('500', WeightUnit::G);

    expect($result)->toBeString()
        ->and($result)->toBe('500.00');
});

test('converts pounds to grams', function () {
    $result = WeightConverter::toGrams('2.5', WeightUnit::Lb);

    expect($result)->toBeString()
        ->and($result)->toBe('1133.98');
});

test('converts ounces to grams', function () {
    $result = WeightConverter::toGrams('16', WeightUnit::Oz);

    expect($result)->toBeString()
        ->and($result)->toBe('453.59');
});

test('handles zero weight', function () {
    $result = WeightConverter::toGrams('0', WeightUnit::Kg);

    expect($result)->toBeString()
        ->and($result)->toBe('0.00');
});

test('handles decimal weight values', function () {
    $result = WeightConverter::toGrams('0.5', WeightUnit::Kg);

    expect($result)->toBeString()
        ->and($result)->toBe('500.00');
});

test('handles large weight values', function () {
    $result = WeightConverter::toGrams('1000', WeightUnit::Kg);

    expect($result)->toBeString()
        ->and($result)->toBe('1000000.00');
});

test('converts all weight units correctly', function () {
    $testCases = [
        [WeightUnit::Kg, '1', '1000.00'],
        [WeightUnit::G, '1000', '1000.00'],
        [WeightUnit::Lb, '1', '453.59'],
        [WeightUnit::Oz, '1', '28.35'],
    ];

    foreach ($testCases as [$unit, $input, $expected]) {
        $result = WeightConverter::toGrams($input, $unit);

        expect($result)->toBeString()
            ->and($result)->toBe($expected);
    }
});

test('maintains precision for decimal conversions', function () {
    $result = WeightConverter::toGrams('2.5', WeightUnit::Lb);

    expect($result)->toBeString()
        ->and($result)->toBe('1133.98');
});

test('handles fractional ounces correctly', function () {
    $result = WeightConverter::toGrams('0.5', WeightUnit::Oz);

    expect($result)->toBeString()
        ->and($result)->toBe('14.17');
});

test('converts grams back to kilograms', function () {
    expect(WeightConverter::fromGrams('1500', WeightUnit::Kg))->toBe('1.50');
});

test('converts grams back to grams', function () {
    expect(WeightConverter::fromGrams('500', WeightUnit::G))->toBe('500.00');
});

test('converts grams back to pounds', function () {
    expect(WeightConverter::fromGrams('1133.98', WeightUnit::Lb))->toBe('2.50');
});

test('converts grams back to ounces', function () {
    expect(WeightConverter::fromGrams('453.59', WeightUnit::Oz))->toBe('16.00');
});
