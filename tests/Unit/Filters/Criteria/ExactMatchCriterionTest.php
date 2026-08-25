<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\ExactMatchCriterion;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

covers(ExactMatchCriterion::class);

uses()->group('filters');

test('can create ExactMatchCriterion with column only', function () {
    $criterion = new ExactMatchCriterion('test_column');

    expect($criterion)->toBeInstanceOf(ExactMatchCriterion::class);
});

test('can create ExactMatchCriterion with column and operator', function () {
    $criterion = new ExactMatchCriterion('test_column', '<>');

    expect($criterion)->toBeInstanceOf(ExactMatchCriterion::class);
});

test('applies exact match filter to builder', function () {
    $criterion = new ExactMatchCriterion('test_column');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('where')
        ->with('test_column', '=', 'test_value')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 'test_value');

    expect($result)->toBe($modifiedBuilder);
});

test('applies exact match filter with custom operator', function () {
    $criterion = new ExactMatchCriterion('test_column', '<>');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('where')
        ->with('test_column', '<>', 'test_value')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 'test_value');

    expect($result)->toBe($modifiedBuilder);
});

test('canApply returns false for null value', function () {
    $criterion = new ExactMatchCriterion('test_column');

    expect($criterion->canApply(null))->toBeFalse();
});

test('canApply returns false for empty string', function () {
    $criterion = new ExactMatchCriterion('test_column');

    expect($criterion->canApply(''))->toBeFalse();
});

test('canApply returns true for valid values', function () {
    $criterion = new ExactMatchCriterion('test_column');

    expect($criterion->canApply('test_value'))->toBeTrue()
        ->and($criterion->canApply(123))->toBeTrue()
        ->and($criterion->canApply(true))->toBeTrue()
        ->and($criterion->canApply(false))->toBeTrue();
});
