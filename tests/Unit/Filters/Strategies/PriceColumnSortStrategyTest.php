<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Strategies;

use App\Filters\Strategies\PriceColumnSortStrategy;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

covers(PriceColumnSortStrategy::class);

uses()->group('filters');

test('applies price sorting strategy with ascending direction', function () {
    $strategy = new PriceColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('leftJoinSub')
        ->once()
        ->andReturnSelf();

    $builder->shouldReceive('orderByRaw')
        ->with('COALESCE(NULLIF(products.price, 0), variant_prices.min_variant_price) asc')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $strategy->apply($builder, 'asc');

    expect($result)->toBe($modifiedBuilder);
});

test('applies price sorting strategy with descending direction', function () {
    $strategy = new PriceColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('leftJoinSub')
        ->once()
        ->andReturnSelf();

    $builder->shouldReceive('orderByRaw')
        ->with('COALESCE(NULLIF(products.price, 0), variant_prices.min_variant_price) desc')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $strategy->apply($builder, 'desc');

    expect($result)->toBe($modifiedBuilder);
});
