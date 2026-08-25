<?php

declare(strict_types=1);

use App\Rules\ProductWeightRule;

covers(ProductWeightRule::class);

uses()->group('rules', 'product');

test('validation is skipped when variants are present', function () {
    $rule = new ProductWeightRule();
    $rule->setData([
        'variants' => ['any' => 'thing'],
        'type' => 'physical',
    ]);

    $failClosureCalled = false;

    $rule->validate('weight', -100, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation passes for valid numeric weight', function () {
    $rule = new ProductWeightRule();
    $rule->setData([
        'variants' => [],
        'type' => 'physical',
    ]);

    foreach ([0.01, 1, 99.99, 100, '23'] as $value) {
        $failClosureCalled = false;

        $rule->validate('weight', $value, function () use (&$failClosureCalled) {
            $failClosureCalled = true;
        });

        expect($failClosureCalled)->toBeFalse();
    }
});

test('validation fails for missing value', function () {
    $rule = new ProductWeightRule();
    $rule->setData([
        'variants' => [],
        'type' => 'physical',
    ]);

    $failClosureCalled = false;

    $rule->validate('weight', null, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation fails for non-numeric value', function () {
    $rule = new ProductWeightRule();
    $rule->setData([
        'variants' => [],
        'type' => 'physical',
    ]);

    $failClosureCalled = false;

    $rule->validate('weight', 'not-a-number', function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation fails for non-positive value', function () {
    $rule = new ProductWeightRule();
    $rule->setData([
        'variants' => [],
        'type' => 'physical',
    ]);

    foreach ([0, '0', -1] as $value) {
        $failClosureCalled = false;

        $rule->validate('weight', $value, function () use (&$failClosureCalled) {
            $failClosureCalled = true;
        });

        expect($failClosureCalled)->toBeTrue();
    }
});
