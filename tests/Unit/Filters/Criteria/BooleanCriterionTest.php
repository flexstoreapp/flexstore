<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\BooleanCriterion;
use App\Models\Product;

covers(BooleanCriterion::class);

uses()->group('filters');

test('applies true filter correctly', function () {
    Product::factory()->active()->create();
    Product::factory()->inactive()->create();
    Product::factory()->active()->create();

    $criterion = new BooleanCriterion('is_active');
    $builder = Product::query();

    $result = $criterion->apply($builder, true);

    expect($result->count())->toBe(2);

    $result->each(function ($product) {
        expect($product->is_active)->toBeTrue();
    });
});

test('applies false filter correctly', function () {
    Product::factory()->active()->create();
    Product::factory()->inactive()->create();
    Product::factory()->inactive()->create();

    $criterion = new BooleanCriterion('is_active');
    $builder = Product::query();

    $result = $criterion->apply($builder, false);

    expect($result->count())->toBe(2);

    $result->each(function ($product) {
        expect($product->is_active)->toBeFalse();
    });
});

test('converts string true values correctly', function () {
    Product::factory()->active()->create();
    Product::factory()->inactive()->create();

    $criterion = new BooleanCriterion('is_active');
    $builder = Product::query();

    $trueValues = ['true', '1', 'yes', 'on'];

    foreach ($trueValues as $value) {
        $result = $criterion->apply(Product::query(), $value);
        expect($result->count())->toBe(1);
        expect($result->first()->is_active)->toBeTrue();
    }
});

test('converts string false values correctly', function () {
    Product::factory()->active()->create();
    Product::factory()->inactive()->create();

    $criterion = new BooleanCriterion('is_active');

    $falseValues = ['false', '0', 'no', 'off', 'anything_else'];

    foreach ($falseValues as $value) {
        $result = $criterion->apply(Product::query(), $value);
        expect($result->count())->toBe(1);
        expect($result->first()->is_active)->toBeFalse();
    }
});

test('canApply returns true for non-null non-empty values', function () {
    $criterion = new BooleanCriterion('is_active');

    expect($criterion->canApply(true))->toBeTrue()
        ->and($criterion->canApply(false))->toBeTrue()
        ->and($criterion->canApply('true'))->toBeTrue()
        ->and($criterion->canApply('false'))->toBeTrue()
        ->and($criterion->canApply('1'))->toBeTrue()
        ->and($criterion->canApply('0'))->toBeTrue()
        ->and($criterion->canApply(1))->toBeTrue()
        ->and($criterion->canApply(0))->toBeTrue();
});

test('canApply returns false for null or empty values', function () {
    $criterion = new BooleanCriterion('is_active');

    expect($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse();
});
