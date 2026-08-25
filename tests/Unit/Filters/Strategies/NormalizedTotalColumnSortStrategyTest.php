<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Strategies;

use App\Filters\Strategies\NormalizedTotalColumnSortStrategy;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

covers(NormalizedTotalColumnSortStrategy::class);

uses()->group('filters');

test('sorts by total divided by exchange_rate ascending', function () {
    $strategy = new NormalizedTotalColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderByRaw')
        ->with('total / exchange_rate asc')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $strategy->apply($builder, 'asc');

    expect($result)->toBe($modifiedBuilder);
});

test('sorts by total divided by exchange_rate descending', function () {
    $strategy = new NormalizedTotalColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderByRaw')
        ->with('total / exchange_rate desc')
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $strategy->apply($builder, 'desc');

    expect($result)->toBe($modifiedBuilder);
});
