<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\CurrencySymbolPosition;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(Currency::class);

uses()->group('models', 'currency');

test('has factory', function () {
    expect(Currency::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $currency = Currency::factory()->create([
        'code' => 'TST',
        'exchange_rate' => '1.2345',
    ]);

    $casts = $currency->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('exchange_rate', 'decimal:4')
        ->toHaveKey('is_active', 'boolean');
});

test('getFormat returns format for existing currency', function () {
    Currency::factory()->create([
        'code' => 'GBP',
        'symbol' => '£',
        'symbol_position' => CurrencySymbolPosition::Before,
        'thousands_separator' => ',',
        'decimal_separator' => '.',
        'decimal_places' => 2,
    ]);

    $format = Currency::getFormat('GBP');

    expect($format)
        ->symbol->toBe('£')
        ->symbol_position->toBe(CurrencySymbolPosition::Before)
        ->thousands_separator->toBe(',')
        ->decimal_separator->toBe('.')
        ->decimal_places->toBe(2);
});

test('getFormat returns defaults for unknown currency', function () {
    $format = Currency::getFormat('XYZ');

    expect($format)
        ->symbol->toBe('XYZ')
        ->symbol_position->toBe(CurrencySymbolPosition::BeforeWithSpace)
        ->decimal_places->toBe(2);
});

test('getDecimalPlaces returns decimal places from getFormat', function () {
    Currency::factory()->create([
        'code' => 'BHD',
        'symbol' => 'BD',
        'decimal_places' => 3,
    ]);

    expect(Currency::getDecimalPlaces('BHD'))->toBe(3);
});

test('getDecimalPlaces returns default for unknown currency', function () {
    expect(Currency::getDecimalPlaces('XYZ'))->toBe(2);
});

test('getExchangeRate returns rate for existing currency', function () {
    Currency::factory()->create([
        'code' => 'EUR',
        'exchange_rate' => '0.8500',
    ]);

    expect(Currency::getExchangeRate('EUR'))->toBe('0.8500');
});

test('getExchangeRate returns default for unknown currency', function () {
    expect(Currency::getExchangeRate('XYZ'))->toBe('1.0000');
});

test('existsByCode reports whether a currency code is configured', function () {
    Currency::factory()->create(['code' => 'EUR']);

    expect(Currency::existsByCode('eur'))->toBeTrue()
        ->and(Currency::existsByCode('XYZ'))->toBeFalse();
});
