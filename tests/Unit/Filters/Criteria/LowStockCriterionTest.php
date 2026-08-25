<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\LowStockCriterion;
use App\Models\Product;
use App\Models\Setting;

covers(LowStockCriterion::class);

uses()->group('filters');

test('canApply returns true for non-empty values', function () {
    $criterion = new LowStockCriterion();

    expect($criterion->canApply('true'))->toBeTrue()
        ->and($criterion->canApply('false'))->toBeTrue()
        ->and($criterion->canApply('1'))->toBeTrue()
        ->and($criterion->canApply(true))->toBeTrue();
});

test('canApply returns false for empty values', function () {
    $criterion = new LowStockCriterion();

    expect($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse();
});

test('does not filter when value is false', function () {
    Product::factory()->create([
        'track_stock' => true,
        'stock' => 3,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    $criterion = new LowStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, 'false');

    expect($result->count())->toBe(2);
});

test('filters products with low stock using default threshold', function () {
    Setting::setValue('default_low_stock_threshold', 5);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 3,
        'low_stock_threshold' => null,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'low_stock_threshold' => null,
    ]);

    $criterion = new LowStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, 'true');

    expect($result->count())->toBe(1)
        ->and($result->first()->stock)->toBe(3);
});

test('filters products with low stock using custom threshold', function () {
    Product::factory()->create([
        'track_stock' => true,
        'stock' => 3,
        'low_stock_threshold' => 5,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'low_stock_threshold' => 5,
    ]);

    $criterion = new LowStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, 'true');

    expect($result->count())->toBe(1)
        ->and($result->first()->stock)->toBe(3);
});

test('filters products at threshold level', function () {
    Setting::setValue('default_low_stock_threshold', 5);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 5,
        'low_stock_threshold' => null,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 6,
        'low_stock_threshold' => null,
    ]);

    $criterion = new LowStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, 'true');

    expect($result->count())->toBe(1)
        ->and($result->first()->stock)->toBe(5);
});

test('only filters products with track_stock enabled', function () {
    Setting::setValue('default_low_stock_threshold', 5);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 3,
    ]);

    Product::factory()->create([
        'track_stock' => false,
        'stock' => 3,
    ]);

    $criterion = new LowStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, 'true');

    expect($result->count())->toBe(1)
        ->and($result->first()->track_stock)->toBeTrue();
});

test('only filters products with stock set', function () {
    Setting::setValue('default_low_stock_threshold', 5);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 3,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => null,
    ]);

    $criterion = new LowStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, 'true');

    expect($result->count())->toBe(1)
        ->and($result->first()->stock)->toBe(3);
});

test('handles boolean true value', function () {
    Setting::setValue('default_low_stock_threshold', 5);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 3,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    $criterion = new LowStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, true);

    expect($result->count())->toBe(1);
});

test('handles string boolean values', function () {
    Setting::setValue('default_low_stock_threshold', 5);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 3,
    ]);

    $criterion = new LowStockCriterion();
    $builder = Product::query();

    expect($criterion->apply($builder, '1')->count())->toBe(1)
        ->and($criterion->apply($builder, 'yes')->count())->toBe(1)
        ->and($criterion->apply($builder, 'on')->count())->toBe(1);
});

test('handles mixed threshold scenarios', function () {
    Setting::setValue('default_low_stock_threshold', 5);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 3,
        'low_stock_threshold' => null,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 2,
        'low_stock_threshold' => 3,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'low_stock_threshold' => null,
    ]);

    $criterion = new LowStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, 'true');

    expect($result->count())->toBe(2);
});
