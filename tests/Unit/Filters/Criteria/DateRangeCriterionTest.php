<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\DateRangeCriterion;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

covers(DateRangeCriterion::class);

uses()->group('filters');

test('can create DateRangeCriterion with column only (defaults to after)', function () {
    $criterion = new DateRangeCriterion('created_at', DateRangeCriterion::TYPE_AFTER);

    expect($criterion)->toBeInstanceOf(DateRangeCriterion::class);
});

test('can create DateRangeCriterion with column and type', function () {
    $criterion = new DateRangeCriterion('created_at', DateRangeCriterion::TYPE_BEFORE);

    expect($criterion)->toBeInstanceOf(DateRangeCriterion::class);
});

test('applies after date filter with >= operator', function () {
    $criterion = new DateRangeCriterion('created_at', DateRangeCriterion::TYPE_AFTER);
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('whereDate')
        ->with('created_at', '>=', '2023-01-01')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, '2023-01-01');

    expect($result)->toBe($modifiedBuilder);
});

test('applies before date filter with <= operator', function () {
    $criterion = new DateRangeCriterion('created_at', DateRangeCriterion::TYPE_BEFORE);
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('whereDate')
        ->with('created_at', '<=', '2023-12-31')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, '2023-12-31');

    expect($result)->toBe($modifiedBuilder);
});

test('defaults to after type when not specified', function () {
    $criterion = new DateRangeCriterion('created_at', DateRangeCriterion::TYPE_AFTER);
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('whereDate')
        ->with('created_at', '>=', '2023-01-01')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, '2023-01-01');

    expect($result)->toBe($modifiedBuilder);
});

test('canApply returns true for valid date string', function () {
    $criterion = new DateRangeCriterion('created_at', DateRangeCriterion::TYPE_AFTER);

    expect($criterion->canApply('2023-01-01'))->toBeTrue()
        ->and($criterion->canApply('2023-12-31'))->toBeTrue()
        ->and($criterion->canApply('2023-06-15 10:30:00'))->toBeTrue();
});

test('canApply returns false for empty string', function () {
    $criterion = new DateRangeCriterion('created_at', DateRangeCriterion::TYPE_AFTER);

    expect($criterion->canApply(''))->toBeFalse();
});

test('canApply returns false for whitespace only string', function () {
    $criterion = new DateRangeCriterion('created_at', DateRangeCriterion::TYPE_AFTER);

    expect($criterion->canApply('   '))->toBeFalse()
        ->and($criterion->canApply("\t"))->toBeFalse()
        ->and($criterion->canApply("\n"))->toBeFalse();
});

test('canApply returns false for null value', function () {
    $criterion = new DateRangeCriterion('created_at', DateRangeCriterion::TYPE_AFTER);

    expect($criterion->canApply(null))->toBeFalse();
});

test('canApply returns false for non-string values', function () {
    $criterion = new DateRangeCriterion('created_at', DateRangeCriterion::TYPE_AFTER);

    expect($criterion->canApply(123))->toBeFalse()
        ->and($criterion->canApply(true))->toBeFalse()
        ->and($criterion->canApply(['2023-01-01']))->toBeFalse()
        ->and($criterion->canApply((object) ['date' => '2023-01-01']))->toBeFalse();
});
