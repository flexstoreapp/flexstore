<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\OnSaleCriterion;
use App\Models\Product;

covers(OnSaleCriterion::class);

uses()->group('filters');

test('filters products whose compare at price is higher than price', function () {
    $onSale = Product::factory()->create(['price' => 50, 'compare_at_price' => 80]);
    Product::factory()->create(['price' => 50, 'compare_at_price' => null]);
    Product::factory()->create(['price' => 50, 'compare_at_price' => 40]);

    $criterion = new OnSaleCriterion();
    $result = $criterion->apply(Product::query(), '1')->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($onSale->id);
});

test('canApply returns true only for truthy values', function () {
    $criterion = new OnSaleCriterion();

    expect($criterion->canApply('1'))->toBeTrue()
        ->and($criterion->canApply(true))->toBeTrue();
});

test('canApply returns false for empty or falsy values', function () {
    $criterion = new OnSaleCriterion();

    expect($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse()
        ->and($criterion->canApply('0'))->toBeFalse();
});
