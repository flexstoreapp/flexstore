<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\SortCriterion;
use App\Filters\Strategies\ProductsCountColumnSortStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Mockery;

covers(SortCriterion::class);

uses()->group('filters');

beforeEach(function () {
    DB::shouldReceive('getDriverName')->andReturn('sqlite');
});

test('can create SortCriterion with allowed columns only', function () {
    $criterion = new SortCriterion(['title', 'price']);

    expect($criterion)->toBeInstanceOf(SortCriterion::class);
});

test('can create SortCriterion with custom defaults', function () {
    $criterion = new SortCriterion(['title', 'price'], 'title', defaultDirection: 'asc');

    expect($criterion)->toBeInstanceOf(SortCriterion::class);
});

test('can create SortCriterion with column strategies', function () {
    $strategy = new ProductsCountColumnSortStrategy();
    $criterion = new SortCriterion(
        ['title', 'products_count'],
        'created_at',
        defaultDirection: 'desc',
        columnStrategies: ['products_count' => $strategy],
    );

    expect($criterion)->toBeInstanceOf(SortCriterion::class);
});

test('canApply always returns true', function () {
    $criterion = new SortCriterion(['title', 'price']);

    expect($criterion->canApply('title'))->toBeTrue()
        ->and($criterion->canApply(null))->toBeTrue()
        ->and($criterion->canApply(''))->toBeTrue()
        ->and($criterion->canApply('invalid_column'))->toBeTrue()
        ->and($criterion->canApply(123))->toBeTrue()
        ->and($criterion->canApply(true))->toBeTrue();
});

test('applies sort with allowed column and default direction', function () {
    $criterion = new SortCriterion(['title', 'price']);
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);
    $tiebreakerBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderByRaw')
        ->with('title desc nulls last')
        ->once()
        ->andReturn($modifiedBuilder);
    $modifiedBuilder->shouldReceive('orderBy')->with('id')->once()->andReturn($tiebreakerBuilder);

    $result = $criterion->apply($builder, 'title');

    expect($result)->toBe($tiebreakerBuilder);
});

test('applies sort with default column when value not in allowed columns', function () {
    $criterion = new SortCriterion(['title', 'price'], 'created_at');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);
    $tiebreakerBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderByRaw')
        ->with('created_at desc nulls last')
        ->once()
        ->andReturn($modifiedBuilder);
    $modifiedBuilder->shouldReceive('orderBy')->with('id')->once()->andReturn($tiebreakerBuilder);

    $result = $criterion->apply($builder, 'invalid_column');

    expect($result)->toBe($tiebreakerBuilder);
});

test('applies sort with custom default column and direction', function () {
    $criterion = new SortCriterion(['title', 'price'], 'title', defaultDirection: 'asc');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);
    $tiebreakerBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderBy')
        ->with('title', 'asc')
        ->once()
        ->andReturn($modifiedBuilder);
    $modifiedBuilder->shouldReceive('orderBy')->with('id')->once()->andReturn($tiebreakerBuilder);

    $result = $criterion->apply($builder, 'invalid_column');

    expect($result)->toBe($tiebreakerBuilder);
});

test('applies default column when value is null', function () {
    $criterion = new SortCriterion(['title', 'price'], 'created_at');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);
    $tiebreakerBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderByRaw')
        ->with('created_at desc nulls last')
        ->once()
        ->andReturn($modifiedBuilder);
    $modifiedBuilder->shouldReceive('orderBy')->with('id')->once()->andReturn($tiebreakerBuilder);

    $result = $criterion->apply($builder, null);

    expect($result)->toBe($tiebreakerBuilder);
});

test('applies default column when value is empty string', function () {
    $criterion = new SortCriterion(['title', 'price'], 'created_at');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);
    $tiebreakerBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderByRaw')
        ->with('created_at desc nulls last')
        ->once()
        ->andReturn($modifiedBuilder);
    $modifiedBuilder->shouldReceive('orderBy')->with('id')->once()->andReturn($tiebreakerBuilder);

    $result = $criterion->apply($builder, '');

    expect($result)->toBe($tiebreakerBuilder);
});

test('applies column strategy when available', function () {
    $strategy = new ProductsCountColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('withCount')
        ->with('products')
        ->once()
        ->andReturnSelf();

    $builder->shouldReceive('orderBy')
        ->with('products_count', 'desc')
        ->once()
        ->andReturn($modifiedBuilder);

    $criterion = new SortCriterion(
        allowedColumns: ['title', 'products_count'],
        columnStrategies: ['products_count' => $strategy],
    );

    $result = $criterion->apply($builder, 'products_count');

    expect($result)->toBe($modifiedBuilder);
});

test('applies regular order when no strategy for column', function () {
    $strategy = new ProductsCountColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);
    $tiebreakerBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderByRaw')
        ->with('title desc nulls last')
        ->once()
        ->andReturn($modifiedBuilder);
    $modifiedBuilder->shouldReceive('orderBy')->with('id')->once()->andReturn($tiebreakerBuilder);

    $criterion = new SortCriterion(
        allowedColumns: ['title', 'products_count'],
        columnStrategies: ['products_count' => $strategy],
    );

    $result = $criterion->apply($builder, 'title');

    expect($result)->toBe($tiebreakerBuilder);
});

test('uses the provided direction', function () {
    $criterion = new SortCriterion(['title', 'price'], direction: 'asc');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);
    $tiebreakerBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderBy')
        ->with('title', 'asc')
        ->once()
        ->andReturn($modifiedBuilder);
    $modifiedBuilder->shouldReceive('orderBy')->with('id')->once()->andReturn($tiebreakerBuilder);

    $result = $criterion->apply($builder, 'title');

    expect($result)->toBe($tiebreakerBuilder);
});

test('uses default direction when provided direction is invalid', function () {
    $criterion = new SortCriterion(['title', 'price'], direction: 'invalid');
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);
    $tiebreakerBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderByRaw')
        ->with('title desc nulls last')
        ->once()
        ->andReturn($modifiedBuilder);
    $modifiedBuilder->shouldReceive('orderBy')->with('id')->once()->andReturn($tiebreakerBuilder);

    $result = $criterion->apply($builder, 'title');

    expect($result)->toBe($tiebreakerBuilder);
});
