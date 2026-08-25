<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\CouponUsageCriterion;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

covers(CouponUsageCriterion::class);

uses()->group('filters');

test('can create CouponUsageCriterion', function () {
    $criterion = new CouponUsageCriterion();

    expect($criterion)->toBeInstanceOf(CouponUsageCriterion::class);
});

test('applies unlimited usage filter', function () {
    $criterion = new CouponUsageCriterion();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('whereNull')
        ->with('usage_limit')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 'unlimited');

    expect($result)->toBe($modifiedBuilder);
});

test('applies limited usage filter', function () {
    $criterion = new CouponUsageCriterion();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('whereNotNull')
        ->with('usage_limit')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 'limited');

    expect($result)->toBe($modifiedBuilder);
});

test('applies available usage filter', function () {
    $criterion = new CouponUsageCriterion();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('where')
        ->with(Mockery::type('Closure'))
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 'available');

    expect($result)->toBe($modifiedBuilder);
});

test('canApply returns true for valid usage values', function () {
    $criterion = new CouponUsageCriterion();

    expect($criterion->canApply('unlimited'))->toBeTrue()
        ->and($criterion->canApply('limited'))->toBeTrue()
        ->and($criterion->canApply('available'))->toBeTrue();
});

test('returns original builder for invalid usage value', function () {
    $criterion = new CouponUsageCriterion();
    $builder = Mockery::mock(Builder::class);

    $result = $criterion->apply($builder, 'invalid');

    expect($result)->toBe($builder);
});

test('canApply returns false for invalid usage values', function () {
    $criterion = new CouponUsageCriterion();

    expect($criterion->canApply('pending'))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse()
        ->and($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(123))->toBeFalse()
        ->and($criterion->canApply(['unlimited']))->toBeFalse();
});

test('available filter includes unlimited coupons', function () {
    $criterion = new CouponUsageCriterion();

    $unlimited = Coupon::factory()->create([
        'usage_limit' => null,
        'used_count' => 100,
    ]);

    $limited = Coupon::factory()->create([
        'usage_limit' => 10,
        'used_count' => 5,
    ]);

    $exhausted = Coupon::factory()->create([
        'usage_limit' => 10,
        'used_count' => 10,
    ]);

    $query = Coupon::query();
    $result = $criterion->apply($query, 'available');

    $ids = $result->pluck('id')->all();

    expect($ids)->toContain($unlimited->id)
        ->and($ids)->toContain($limited->id)
        ->and($ids)->not->toContain($exhausted->id);
});

test('available filter excludes exhausted limited coupons', function () {
    $criterion = new CouponUsageCriterion();

    $exhausted = Coupon::factory()->create([
        'usage_limit' => 5,
        'used_count' => 5,
    ]);

    $overUsed = Coupon::factory()->create([
        'usage_limit' => 5,
        'used_count' => 10,
    ]);

    $query = Coupon::query();
    $result = $criterion->apply($query, 'available');

    $ids = $result->pluck('id')->all();

    expect($ids)->not->toContain($exhausted->id)
        ->and($ids)->not->toContain($overUsed->id);
});
