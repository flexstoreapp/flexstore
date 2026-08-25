<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Utilities\CurrencyAmount;
use Illuminate\Support\Facades\Cache;

covers(CurrencyAmount::class);

uses()->group('utilities', 'currency');

beforeEach(function () {
    Currency::query()->updateOrCreate(
        ['code' => 'USD'],
        [
            'symbol' => '$',
            'exchange_rate' => '1.0000',
            'decimal_places' => 2,
            'is_active' => true,
        ]
    );

    Currency::query()->updateOrCreate(
        ['code' => 'JPY'],
        [
            'symbol' => '¥',
            'exchange_rate' => '150.0000',
            'decimal_places' => 0,
            'is_active' => true,
        ]
    );

    Currency::query()->updateOrCreate(
        ['code' => 'BHD'],
        [
            'symbol' => 'BD',
            'exchange_rate' => '0.3770',
            'decimal_places' => 3,
            'is_active' => true,
        ]
    );

    Cache::flush();
});

test('converts amount to smallest unit for USD', function () {
    expect(CurrencyAmount::toSmallestUnit('19.99', 'USD'))->toBe(1999);
});

test('converts amount to smallest unit for zero-decimal currency', function () {
    expect(CurrencyAmount::toSmallestUnit('500', 'JPY'))->toBe(500);
});

test('converts amount to smallest unit for three-decimal currency', function () {
    expect(CurrencyAmount::toSmallestUnit('10.500', 'BHD'))->toBe(10500);
});

test('converts from smallest unit for USD', function () {
    expect(CurrencyAmount::fromSmallestUnit(1999, 'USD'))->toBe('19.9900');
});

test('converts from smallest unit for zero-decimal currency', function () {
    expect(CurrencyAmount::fromSmallestUnit(500, 'JPY'))->toBe('500.0000');
});

test('converts from smallest unit for three-decimal currency', function () {
    expect(CurrencyAmount::fromSmallestUnit(10500, 'BHD'))->toBe('10.5000');
});

test('converts from smallest unit accepts string input', function () {
    expect(CurrencyAmount::fromSmallestUnit('1999', 'USD'))->toBe('19.9900');
});

test('formats amount for API with correct decimal places', function () {
    expect(CurrencyAmount::format('19.9900', 'USD'))->toBe('19.99');
});

test('formats amount for API with zero-decimal currency', function () {
    expect(CurrencyAmount::format('500.0000', 'JPY'))->toBe('500');
});

test('formats amount for API with three-decimal currency', function () {
    expect(CurrencyAmount::format('10.5000', 'BHD'))->toBe('10.500');
});

test('rounds half-up when converting to smallest unit', function () {
    expect(CurrencyAmount::toSmallestUnit('19.995', 'USD'))->toBe(2000);
});

test('rounds half-up when formatting for API', function () {
    expect(CurrencyAmount::format('19.995', 'USD'))->toBe('20.00');
});
