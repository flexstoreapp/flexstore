<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\NumericRangeCriterion;
use App\Models\Product;

covers(NumericRangeCriterion::class);

uses()->group('filters');

test('applies minimum value filter correctly', function () {
    Product::factory()->create(['price' => '50.0000']);
    Product::factory()->create(['price' => '100.0000']);
    Product::factory()->create(['price' => '150.0000']);

    $criterion = new NumericRangeCriterion('price', NumericRangeCriterion::TYPE_MIN);
    $builder = Product::query();

    $result = $criterion->apply($builder, '100.0000');

    expect($result->count())->toBe(2);

    $prices = $result->pluck('price')->all();
    expect($prices)->toContain('100.0000')
        ->and($prices)->toContain('150.0000')
        ->and($prices)->not->toContain('50.0000');
});

test('applies maximum value filter correctly', function () {
    Product::factory()->create(['price' => '50.0000']);
    Product::factory()->create(['price' => '100.0000']);
    Product::factory()->create(['price' => '150.0000']);

    $criterion = new NumericRangeCriterion('price', NumericRangeCriterion::TYPE_MAX);
    $builder = Product::query();

    $result = $criterion->apply($builder, '100.0000');

    expect($result->count())->toBe(2);

    $prices = $result->pluck('price')->all();
    expect($prices)->toContain('50.0000')
        ->and($prices)->toContain('100.0000')
        ->and($prices)->not->toContain('150.0000');
});

test('handles string numeric values', function () {
    Product::factory()->create(['price' => '50.0000']);
    Product::factory()->create(['price' => '100.0000']);
    Product::factory()->create(['price' => '150.0000']);

    $criterion = new NumericRangeCriterion('price', NumericRangeCriterion::TYPE_MIN);
    $builder = Product::query();

    $result = $criterion->apply($builder, '100');

    expect($result->count())->toBe(2);
});

test('handles decimal values', function () {
    Product::factory()->create(['price' => '9.0000']);
    Product::factory()->create(['price' => '10.5000']);
    Product::factory()->create(['price' => '15.0000']);

    $criterion = new NumericRangeCriterion('price', NumericRangeCriterion::TYPE_MIN);
    $builder = Product::query();

    $result = $criterion->apply($builder, '10.2500');

    expect($result->count())->toBe(2);
});

test('canApply returns true for numeric values', function () {
    $criterion = new NumericRangeCriterion('price', NumericRangeCriterion::TYPE_MIN);

    expect($criterion->canApply(100))->toBeTrue()
        ->and($criterion->canApply('100.0000'))->toBeTrue()
        ->and($criterion->canApply(99.99))->toBeTrue()
        ->and($criterion->canApply('99.9900'))->toBeTrue()
        ->and($criterion->canApply(0))->toBeTrue()
        ->and($criterion->canApply('0'))->toBeTrue();
});

test('canApply returns false for non-numeric values', function () {
    $criterion = new NumericRangeCriterion('price', NumericRangeCriterion::TYPE_MIN);

    expect($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse()
        ->and($criterion->canApply('abc'))->toBeFalse()
        ->and($criterion->canApply([]))->toBeFalse()
        ->and($criterion->canApply(true))->toBeFalse();
});
