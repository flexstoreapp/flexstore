<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Strategies;

use App\Filters\Strategies\ProductsCountColumnSortStrategy;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

covers(ProductsCountColumnSortStrategy::class);

uses()->group('filters');

test('applies products count sorting strategy with ascending direction', function () {
    $strategy = new ProductsCountColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('withCount')
        ->with('products')
        ->once()
        ->andReturnSelf();

    $builder->shouldReceive('orderBy')
        ->with('products_count', 'asc')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $strategy->apply($builder, 'asc');

    expect($result)->toBe($modifiedBuilder);
});

test('applies products count sorting strategy with descending direction', function () {
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

    $result = $strategy->apply($builder, 'desc');

    expect($result)->toBe($modifiedBuilder);
});
