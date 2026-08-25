<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\RelationshipCriterion;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

covers(RelationshipCriterion::class);

uses()->group('filters');

test('can create RelationshipCriterion with relationship and column', function () {
    $criterion = new RelationshipCriterion('category', 'name');

    expect($criterion)->toBeInstanceOf(RelationshipCriterion::class);
});

test('can create RelationshipCriterion with custom operator', function () {
    $criterion = new RelationshipCriterion('category', 'name', 'like');

    expect($criterion)->toBeInstanceOf(RelationshipCriterion::class);
});

test('applies relationship filter with default equals operator', function () {
    $criterion = new RelationshipCriterion('category', 'name');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('whereRelation')
        ->with('category', 'name', '=', 'Electronics')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 'Electronics');

    expect($result)->toBe($modifiedBuilder);
});

test('applies relationship filter with custom operator', function () {
    $criterion = new RelationshipCriterion('category', 'name', 'like');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('whereRelation')
        ->with('category', 'name', 'like', '%Electronics%')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, '%Electronics%');

    expect($result)->toBe($modifiedBuilder);
});

test('applies relationship filter with numeric value', function () {
    $criterion = new RelationshipCriterion('category', 'id');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('whereRelation')
        ->with('category', 'id', '=', 123)
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 123);

    expect($result)->toBe($modifiedBuilder);
});

test('applies relationship filter with greater than operator', function () {
    $criterion = new RelationshipCriterion('category', 'priority', '>');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('whereRelation')
        ->with('category', 'priority', '>', 5)
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $criterion->apply($builder, 5);

    expect($result)->toBe($modifiedBuilder);
});

test('canApply returns true for valid string', function () {
    $criterion = new RelationshipCriterion('category', 'name');

    expect($criterion->canApply('test'))->toBeTrue()
        ->and($criterion->canApply('Electronics'))->toBeTrue()
        ->and($criterion->canApply('123'))->toBeTrue();
});

test('canApply returns true for numeric values', function () {
    $criterion = new RelationshipCriterion('category', 'id');

    expect($criterion->canApply(123))->toBeTrue()
        ->and($criterion->canApply(0))->toBeTrue()
        ->and($criterion->canApply(-1))->toBeTrue()
        ->and($criterion->canApply(3.14))->toBeTrue();
});

test('canApply returns true for boolean values', function () {
    $criterion = new RelationshipCriterion('category', 'is_active');

    expect($criterion->canApply(true))->toBeTrue()
        ->and($criterion->canApply(false))->toBeTrue();
});

test('canApply returns false for empty string', function () {
    $criterion = new RelationshipCriterion('category', 'name');

    expect($criterion->canApply(''))->toBeFalse();
});

test('canApply returns false for null value', function () {
    $criterion = new RelationshipCriterion('category', 'name');

    expect($criterion->canApply(null))->toBeFalse();
});
