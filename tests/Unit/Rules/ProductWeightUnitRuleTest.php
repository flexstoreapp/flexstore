<?php

declare(strict_types=1);

use App\Enums\WeightUnit;
use App\Rules\ProductWeightUnitRule;

covers(ProductWeightUnitRule::class);

uses()->group('rules', 'product');

test('validation is skipped when variants are present', function () {
    $rule = new ProductWeightUnitRule();
    $rule->setData([
        'variants' => ['any' => 'thing'],
        'type' => 'physical',
    ]);

    $failClosureCalled = false;

    $rule->validate('weight_unit', 'not-valid-unit', function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation passes for valid weight units', function () {
    $rule = new ProductWeightUnitRule();
    $rule->setData([
        'variants' => [],
        'type' => 'physical',
    ]);

    foreach (WeightUnit::cases() as $unit) {
        $failClosureCalled = false;

        $rule->validate('weight_unit', $unit->value, function () use (&$failClosureCalled) {
            $failClosureCalled = true;
        });

        expect($failClosureCalled)->toBeFalse();
    }
});

test('validation fails for missing value', function () {
    $rule = new ProductWeightUnitRule();
    $rule->setData([
        'variants' => [],
        'type' => 'physical',
    ]);

    $failClosureCalled = false;

    $rule->validate('weight_unit', null, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation fails for invalid string value', function () {
    $rule = new ProductWeightUnitRule();
    $rule->setData([
        'variants' => [],
        'type' => 'physical',
    ]);

    $failClosureCalled = false;

    $rule->validate('weight_unit', 12345, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation fails for invalid weight unit value', function () {
    $rule = new ProductWeightUnitRule();
    $rule->setData([
        'variants' => [],
        'type' => 'physical',
    ]);

    $failClosureCalled = false;

    $rule->validate('weight_unit', 'invalid-weight-unit', function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});
