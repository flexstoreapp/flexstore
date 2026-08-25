<?php

declare(strict_types=1);

use App\Rules\ProductStockRule;

covers(ProductStockRule::class);

uses()->group('rules', 'product');

test('validation passes when variants are present and stock is empty', function () {
    $rule = new ProductStockRule();
    $rule->setData([
        'variants' => ['any' => 'thing'],
        'track_stock' => true,
    ]);

    $failClosureCalled = false;

    $rule->validate('stock', null, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation is skipped when not tracking quantity', function () {
    $rule = new ProductStockRule();
    $rule->setData([
        'variants' => [],
        'track_stock' => false,
    ]);

    foreach ([null, '', 0, 10, -5] as $value) {
        $failClosureCalled = false;

        $rule->validate('stock', $value, function () use (&$failClosureCalled) {
            $failClosureCalled = true;
        });

        expect($failClosureCalled)->toBeFalse();
    }
});

test('validation passes for valid numeric quantity', function () {
    $rule = new ProductStockRule();
    $rule->setData([
        'variants' => [],
        'track_stock' => true,
    ]);

    foreach ([0, 1, 99, 100, '23'] as $value) {
        $failClosureCalled = false;

        $rule->validate('stock', $value, function () use (&$failClosureCalled) {
            $failClosureCalled = true;
        });

        expect($failClosureCalled)->toBeFalse();
    }
});

test('validation fails for missing value', function () {
    $rule = new ProductStockRule();
    $rule->setData([
        'variants' => [],
        'track_stock' => true,
    ]);

    $failClosureCalled = false;

    $rule->validate('stock', null, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation fails for non-numeric value', function () {
    $rule = new ProductStockRule();
    $rule->setData([
        'variants' => [],
        'track_stock' => true,
    ]);

    $failClosureCalled = false;

    $rule->validate('stock', 'not-a-number', function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation fails for negative value', function () {
    $rule = new ProductStockRule();
    $rule->setData([
        'variants' => [],
        'track_stock' => true,
    ]);

    $failClosureCalled = false;

    $rule->validate('stock', -1, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});
