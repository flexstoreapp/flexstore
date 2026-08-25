<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Strategies;

use App\Filters\Strategies\StockColumnSortStrategy;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

covers(StockColumnSortStrategy::class);

uses()->group('filters');

test('applies stock sorting strategy with ascending direction', function () {
    $strategy = new StockColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('leftJoinSub')
        ->once()
        ->andReturnSelf();

    $builder->shouldReceive('orderByRaw')
        ->with('COALESCE(NULLIF(products.stock, 0), variant_quantities.total_variant_quantity, 0) asc')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $strategy->apply($builder, 'asc');

    expect($result)->toBe($modifiedBuilder);
});

test('applies stock sorting strategy with descending direction', function () {
    $strategy = new StockColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('leftJoinSub')
        ->once()
        ->andReturnSelf();

    $builder->shouldReceive('orderByRaw')
        ->with('COALESCE(NULLIF(products.stock, 0), variant_quantities.total_variant_quantity, 0) desc')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $strategy->apply($builder, 'desc');

    expect($result)->toBe($modifiedBuilder);
});
