<?php

declare(strict_types=1);

use App\Enums\CurrencySymbolPosition;
use App\Enums\SettingGroup;
use App\Enums\SettingType;
use App\Models\Currency;
use App\Models\Setting;
use App\Utilities\MoneyFormatter;

covers(MoneyFormatter::class);

uses()->group('utilities', 'currency');

beforeEach(function () {
    Setting::query()->updateOrCreate(
        ['key' => 'base_currency'],
        [
            'value' => 'USD',
            'group' => SettingGroup::Currency,
            'type' => SettingType::Text,
        ]
    );

    Currency::query()->updateOrCreate(
        ['code' => 'USD'],
        [
            'symbol' => '$',
            'symbol_position' => CurrencySymbolPosition::Before,
            'exchange_rate' => '1.0000',
            'thousands_separator' => ',',
            'decimal_separator' => '.',
            'decimal_places' => 2,
            'is_active' => true,
        ]
    );

    Cache::flush();
});

test('formats with currency symbol before amount', function () {
    expect(MoneyFormatter::format(1234.56, 'USD'))->toBe('$1,234.56');
});

test('uses base currency when currency code is null', function () {
    expect(MoneyFormatter::format(1234.56))->toBe('$1,234.56');
});

test('formats integer values', function () {
    expect(MoneyFormatter::format(1234, 'USD'))->toBe('$1,234.00');
});

test('formats string numeric values', function () {
    expect(MoneyFormatter::format('1234.5600', 'USD'))->toBe('$1,234.56');
});

test('formats zero value', function () {
    expect(MoneyFormatter::format(0, 'USD'))->toBe('$0.00');
});

test('formats large numbers with thousands separator', function () {
    expect(MoneyFormatter::format(1234567.89, 'USD'))->toBe('$1,234,567.89');
});

test('formats small decimal values', function () {
    expect(MoneyFormatter::format(0.01, 'USD'))->toBe('$0.01');
});

test('rounds values with many decimal places', function () {
    expect(MoneyFormatter::format(1234.56789, 'USD'))->toBe('$1,234.57');
});

test('handles invalid numeric string by returning zero', function () {
    expect(MoneyFormatter::format('invalid', 'USD'))->toBe('$0.00');
});

test('handles empty string by returning zero', function () {
    expect(MoneyFormatter::format('', 'USD'))->toBe('$0.00');
});

test('formats negative values', function () {
    expect(MoneyFormatter::format(-1234.56, 'USD'))->toBe('-$1,234.56');
});

test('respects currency decimal places', function () {
    Currency::factory()->create([
        'code' => 'BHD',
        'symbol' => 'BD',
        'symbol_position' => CurrencySymbolPosition::Before,
        'decimal_places' => 3,
    ]);

    expect(MoneyFormatter::format(1234.5, 'BHD'))->toBe('BD1,234.500');
});

test('respects currency separators', function () {
    Currency::factory()->create([
        'code' => 'EUR',
        'symbol' => '€',
        'symbol_position' => CurrencySymbolPosition::AfterWithSpace,
        'thousands_separator' => '.',
        'decimal_separator' => ',',
    ]);

    expect(MoneyFormatter::format(1234.56, 'EUR'))->toBe('1.234,56 €');
});

test('formats zero decimal places', function () {
    Currency::factory()->create([
        'code' => 'JPY',
        'symbol' => '¥',
        'symbol_position' => CurrencySymbolPosition::Before,
        'decimal_places' => 0,
    ]);

    expect(MoneyFormatter::format(1234.56, 'JPY'))->toBe('¥1,235');
});

test('falls back to defaults when currency not found', function () {
    expect(MoneyFormatter::format(1234.56, 'XYZ'))->toBe('XYZ 1,234.56');
});

test('formats with symbol before with space', function () {
    Currency::factory()->create([
        'code' => 'CAD',
        'symbol' => 'CA$',
        'symbol_position' => CurrencySymbolPosition::BeforeWithSpace,
    ]);

    expect(MoneyFormatter::format(1234.56, 'CAD'))->toBe('CA$ 1,234.56');
});

test('formats with symbol after amount', function () {
    Currency::factory()->create([
        'code' => 'SEK',
        'symbol' => 'kr',
        'symbol_position' => CurrencySymbolPosition::After,
    ]);

    expect(MoneyFormatter::format(1234.56, 'SEK'))->toBe('1,234.56kr');
});

test('withSymbol wraps value with currency symbol', function () {
    expect(MoneyFormatter::withSymbol('50'))->toBe('$50');
});

test('withSymbol uses base currency when currency code is null', function () {
    expect(MoneyFormatter::withSymbol('100'))->toBe('$100');
});

test('withSymbol respects symbol position', function () {
    Currency::factory()->create([
        'code' => 'EUR',
        'symbol' => '€',
        'symbol_position' => CurrencySymbolPosition::AfterWithSpace,
    ]);

    expect(MoneyFormatter::withSymbol('50', 'EUR'))->toBe('50 €');
});

test('withSymbol falls back to currency code when currency not found', function () {
    expect(MoneyFormatter::withSymbol('50', 'XYZ'))->toBe('XYZ 50');
});

test('toDecimal scales value to four decimal places', function () {
    expect(MoneyFormatter::toDecimal('120.4423'))->toBe('120.4423')
        ->and(MoneyFormatter::toDecimal('99.9'))->toBe('99.9000')
        ->and(MoneyFormatter::toDecimal('33.33555'))->toBe('33.3356')
        ->and(MoneyFormatter::toDecimal(0))->toBe('0.0000')
        ->and(MoneyFormatter::toDecimal(null))->toBe('0.0000')
        ->and(MoneyFormatter::toDecimal('-5.5'))->toBe('-5.5000');
});

test('divideToDecimal divides and scales to four decimal places', function () {
    expect(MoneyFormatter::divideToDecimal('100', 3))->toBe('33.3333')
        ->and(MoneyFormatter::divideToDecimal('10', 4))->toBe('2.5000')
        ->and(MoneyFormatter::divideToDecimal(null, 5))->toBe('0.0000');
});

test('divideToDecimal returns zero when divisor is zero or negative', function () {
    expect(MoneyFormatter::divideToDecimal('100', 0))->toBe('0.0000')
        ->and(MoneyFormatter::divideToDecimal('100', -1))->toBe('0.0000');
});
