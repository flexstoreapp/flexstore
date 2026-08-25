<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\CouponStatusCriterion;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

covers(CouponStatusCriterion::class);

uses()->group('filters');

test('can create CouponStatusCriterion', function () {
    $criterion = new CouponStatusCriterion();

    expect($criterion)->toBeInstanceOf(CouponStatusCriterion::class);
});

test('applies active status filter', function () {
    $criterion = new CouponStatusCriterion();
    $builder = Mockery::mock(Builder::class);
    $innerBuilder1 = Mockery::mock(Builder::class);
    $innerBuilder2 = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('where')
        ->with('is_active', true)
        ->once()
        ->andReturnSelf();

    $builder->shouldReceive('where')
        ->with(Mockery::type('Closure'))
        ->twice()
        ->andReturnUsing(function ($closure) use ($innerBuilder1, $innerBuilder2, $modifiedBuilder, $builder) {
            static $callCount = 0;
            $callCount++;

            if ($callCount === 1) {
                $closure($innerBuilder1);

                return $builder;
            }

            $closure($innerBuilder2);

            return $modifiedBuilder;
        });

    $innerBuilder1->shouldReceive('whereNull')
        ->with('starts_at')
        ->once()
        ->andReturnSelf();

    $innerBuilder1->shouldReceive('orWhere')
        ->with('starts_at', '<=', Mockery::type('object'))
        ->once()
        ->andReturnSelf();

    $innerBuilder2->shouldReceive('whereNull')
        ->with('expires_at')
        ->once()
        ->andReturnSelf();

    $innerBuilder2->shouldReceive('orWhere')
        ->with('expires_at', '>=', Mockery::type('object'))
        ->once()
        ->andReturnSelf();

    $result = $criterion->apply($builder, 'active');

    expect($result)->toBe($modifiedBuilder);
});

test('applies inactive status filter', function () {
    $criterion = new CouponStatusCriterion();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('where')
        ->with('is_active', false)
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 'inactive');

    expect($result)->toBe($modifiedBuilder);
});

test('applies expired status filter', function () {
    $criterion = new CouponStatusCriterion();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('where')
        ->with('expires_at', '<', Mockery::type('object'))
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 'expired');

    expect($result)->toBe($modifiedBuilder);
});

test('applies scheduled status filter', function () {
    $criterion = new CouponStatusCriterion();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('where')
        ->with('starts_at', '>', Mockery::type('object'))
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 'scheduled');

    expect($result)->toBe($modifiedBuilder);
});

test('returns original builder for invalid status', function () {
    $criterion = new CouponStatusCriterion();
    $builder = Mockery::mock(Builder::class);

    $result = $criterion->apply($builder, 'invalid');

    expect($result)->toBe($builder);
});

test('canApply returns true for valid status values', function () {
    $criterion = new CouponStatusCriterion();

    expect($criterion->canApply('active'))->toBeTrue()
        ->and($criterion->canApply('inactive'))->toBeTrue()
        ->and($criterion->canApply('expired'))->toBeTrue()
        ->and($criterion->canApply('scheduled'))->toBeTrue();
});

test('canApply returns false for invalid status values', function () {
    $criterion = new CouponStatusCriterion();

    expect($criterion->canApply('pending'))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse()
        ->and($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(123))->toBeFalse()
        ->and($criterion->canApply(['active']))->toBeFalse();
});
