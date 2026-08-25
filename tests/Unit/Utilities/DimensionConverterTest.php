<?php

declare(strict_types=1);

namespace Tests\Unit\Utilities;

use App\Enums\DimensionUnit;
use App\Utilities\DimensionConverter;

covers(DimensionConverter::class);

uses()->group('utilities', 'distance');

test('converts centimeters to centimeters (no conversion)', function () {
    $result = DimensionConverter::toCentimeters('30', DimensionUnit::Cm);

    expect($result)->toBeString()
        ->and($result)->toBe('30.00');
});

test('converts millimeters to centimeters', function () {
    $result = DimensionConverter::toCentimeters('250', DimensionUnit::Mm);

    expect($result)->toBeString()
        ->and($result)->toBe('25.00');
});

test('converts inches to centimeters', function () {
    $result = DimensionConverter::toCentimeters('10', DimensionUnit::In);

    expect($result)->toBeString()
        ->and($result)->toBe('25.40');
});

test('handles zero', function () {
    $result = DimensionConverter::toCentimeters('0', DimensionUnit::In);

    expect($result)->toBeString()
        ->and($result)->toBe('0.00');
});

test('converts all distance units correctly', function () {
    $testCases = [
        [DimensionUnit::Cm, '1', '1.00'],
        [DimensionUnit::Mm, '10', '1.00'],
        [DimensionUnit::In, '1', '2.54'],
    ];

    foreach ($testCases as [$unit, $input, $expected]) {
        $result = DimensionConverter::toCentimeters($input, $unit);

        expect($result)->toBeString()
            ->and($result)->toBe($expected);
    }
});

test('maintains precision for decimal conversions', function () {
    $result = DimensionConverter::toCentimeters('12.5', DimensionUnit::In);

    expect($result)->toBeString()
        ->and($result)->toBe('31.75');
});
