<?php

declare(strict_types=1);

use App\Utilities\CurrencyConverter;

covers(CurrencyConverter::class);

uses()->group('currency');

test('converts amount with exchange rate', function () {
    $converter = new CurrencyConverter();

    expect($converter->convert('100.0000', '0.9200'))->toBe('92.0000');
});

test('converts amount with rate of 1', function () {
    $converter = new CurrencyConverter();

    expect($converter->convert('100.0000', '1'))->toBe('100.0000');
});

test('rounds converted amount to currency decimal places', function () {
    $converter = new CurrencyConverter();

    expect($converter->convert('33.3300', '0.9200'))->toBe('30.6600');
});

test('rounds to specified decimal places', function () {
    $converter = new CurrencyConverter();

    expect($converter->convert('33.3300', '0.9200', 3))->toBe('30.6640');
    expect($converter->convert('33.3300', '0.9200', 0))->toBe('31.0000');
});

test('converts back to base currency', function () {
    $converter = new CurrencyConverter();

    expect($converter->convertBack('92.0000', '0.9200'))->toBe('100.0000');
});

test('handles large amounts', function () {
    $converter = new CurrencyConverter();

    expect($converter->convert('99999.9900', '1.3500'))->toBe('134999.9900');
});

test('handles small exchange rates', function () {
    $converter = new CurrencyConverter();

    expect($converter->convert('100.0000', '0.0091'))->toBe('0.9100');
});

test('rounds amount to currency decimal places', function () {
    $converter = new CurrencyConverter();

    expect($converter->round('120.4423', 2))->toBe('120.4400')
        ->and($converter->round('99.9950', 2))->toBe('100.0000')
        ->and($converter->round('50.1234', 0))->toBe('50.0000')
        ->and($converter->round('33.3335', 3))->toBe('33.3340');
});
