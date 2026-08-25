<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\RelationshipExistsCriterion;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

covers(RelationshipExistsCriterion::class);

uses()->group('filters');

test('can create RelationshipExistsCriterion with relationship', function () {
    $criterion = new RelationshipExistsCriterion('category');

    expect($criterion)->toBeInstanceOf(RelationshipExistsCriterion::class);
});

test('applies has relationship when value is true', function () {
    $criterion = new RelationshipExistsCriterion('category');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('has')
        ->with('category')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, true);

    expect($result)->toBe($modifiedBuilder);
});

test('applies doesntHave relationship when value is false', function () {
    $criterion = new RelationshipExistsCriterion('category');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('doesntHave')
        ->with('category')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, false);

    expect($result)->toBe($modifiedBuilder);
});

test('applies has relationship when string value is true', function () {
    $criterion = new RelationshipExistsCriterion('category');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('has')
        ->with('category')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 'true');

    expect($result)->toBe($modifiedBuilder);
});

test('applies has relationship for truthy string values', function () {
    $criterion = new RelationshipExistsCriterion('category');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('has')
        ->with('category')
        ->times(4)
        ->andReturn($modifiedBuilder);

    expect($criterion->apply($builder, '1'))->toBe($modifiedBuilder)
        ->and($criterion->apply($builder, 'yes'))->toBe($modifiedBuilder)
        ->and($criterion->apply($builder, 'on'))->toBe($modifiedBuilder)
        ->and($criterion->apply($builder, 'TRUE'))->toBe($modifiedBuilder);
});

test('applies doesntHave relationship for falsy string values', function () {
    $criterion = new RelationshipExistsCriterion('category');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('doesntHave')
        ->with('category')
        ->times(4)
        ->andReturn($modifiedBuilder);

    expect($criterion->apply($builder, 'false'))->toBe($modifiedBuilder)
        ->and($criterion->apply($builder, '0'))->toBe($modifiedBuilder)
        ->and($criterion->apply($builder, 'no'))->toBe($modifiedBuilder)
        ->and($criterion->apply($builder, 'off'))->toBe($modifiedBuilder);
});

test('applies has relationship for truthy numeric values', function () {
    $criterion = new RelationshipExistsCriterion('category');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('has')
        ->with('category')
        ->times(3)
        ->andReturn($modifiedBuilder);

    expect($criterion->apply($builder, 1))->toBe($modifiedBuilder)
        ->and($criterion->apply($builder, 123))->toBe($modifiedBuilder)
        ->and($criterion->apply($builder, -1))->toBe($modifiedBuilder);
});

test('applies doesntHave relationship for falsy numeric values', function () {
    $criterion = new RelationshipExistsCriterion('category');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('doesntHave')
        ->with('category')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 0);

    expect($result)->toBe($modifiedBuilder);
});

test('canApply returns true for boolean values', function () {
    $criterion = new RelationshipExistsCriterion('category');

    expect($criterion->canApply(true))->toBeTrue()
        ->and($criterion->canApply(false))->toBeTrue();
});

test('canApply returns true for string values', function () {
    $criterion = new RelationshipExistsCriterion('category');

    expect($criterion->canApply('true'))->toBeTrue()
        ->and($criterion->canApply('false'))->toBeTrue()
        ->and($criterion->canApply('1'))->toBeTrue()
        ->and($criterion->canApply('0'))->toBeTrue()
        ->and($criterion->canApply('yes'))->toBeTrue()
        ->and($criterion->canApply('no'))->toBeTrue();
});

test('canApply returns true for numeric values', function () {
    $criterion = new RelationshipExistsCriterion('category');

    expect($criterion->canApply(1))->toBeTrue()
        ->and($criterion->canApply(0))->toBeTrue()
        ->and($criterion->canApply(123))->toBeTrue()
        ->and($criterion->canApply(-1))->toBeTrue();
});

test('canApply returns false for empty string', function () {
    $criterion = new RelationshipExistsCriterion('category');

    expect($criterion->canApply(''))->toBeFalse();
});

test('canApply returns false for null value', function () {
    $criterion = new RelationshipExistsCriterion('category');

    expect($criterion->canApply(null))->toBeFalse();
});
