<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Strategies;

use App\Filters\Strategies\CustomerNameColumnSortStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Mockery;

covers(CustomerNameColumnSortStrategy::class);

uses()->group('filters');

test('applies customer name sorting strategy with ascending direction', function () {
    DB::shouldReceive('getDriverName')->andReturn('sqlite');

    $strategy = new CustomerNameColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $builder2 = Mockery::mock(Builder::class);
    $builder3 = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('select')
        ->with('orders.*')
        ->once()
        ->andReturn($builder2);

    $builder2->shouldReceive('leftJoin')
        ->once()
        ->andReturn($builder3);

    $builder3->shouldReceive('orderByRaw')
        ->with("(COALESCE(order_addresses.first_name, '') || ' ' || COALESCE(order_addresses.last_name, '')) asc")
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $strategy->apply($builder, 'asc');

    expect($result)->toBe($modifiedBuilder);
});

test('applies customer name sorting strategy with descending direction', function () {
    DB::shouldReceive('getDriverName')->andReturn('sqlite');

    $strategy = new CustomerNameColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $builder2 = Mockery::mock(Builder::class);
    $builder3 = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('select')
        ->with('orders.*')
        ->once()
        ->andReturn($builder2);

    $builder2->shouldReceive('leftJoin')
        ->once()
        ->andReturn($builder3);

    $builder3->shouldReceive('orderByRaw')
        ->with("(COALESCE(order_addresses.first_name, '') || ' ' || COALESCE(order_addresses.last_name, '')) desc")
        ->once()
        ->andReturn($modifiedBuilder);

    $result = $strategy->apply($builder, 'desc');

    expect($result)->toBe($modifiedBuilder);
});

test('uses CONCAT expression on non-sqlite/pgsql databases for ascending direction', function () {
    DB::shouldReceive('getDriverName')->andReturn('mysql');

    $strategy = new CustomerNameColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $builder2 = Mockery::mock(Builder::class);
    $builder3 = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('select')->with('orders.*')->andReturn($builder2);
    $builder2->shouldReceive('leftJoin')->andReturn($builder3);
    $builder3->shouldReceive('orderByRaw')
        ->with("CONCAT(COALESCE(order_addresses.first_name, ''), ' ', COALESCE(order_addresses.last_name, '')) asc")
        ->once()
        ->andReturn($modifiedBuilder);

    expect($strategy->apply($builder, 'asc'))->toBe($modifiedBuilder);
});

test('uses CONCAT expression on non-sqlite/pgsql databases for descending direction', function () {
    DB::shouldReceive('getDriverName')->andReturn('mysql');

    $strategy = new CustomerNameColumnSortStrategy();
    $builder = Mockery::mock(Builder::class);
    $builder2 = Mockery::mock(Builder::class);
    $builder3 = Mockery::mock(Builder::class);
    $modifiedBuilder = Mockery::mock(Builder::class);

    $builder->shouldReceive('select')->with('orders.*')->andReturn($builder2);
    $builder2->shouldReceive('leftJoin')->andReturn($builder3);
    $builder3->shouldReceive('orderByRaw')
        ->with("CONCAT(COALESCE(order_addresses.first_name, ''), ' ', COALESCE(order_addresses.last_name, '')) desc")
        ->once()
        ->andReturn($modifiedBuilder);

    expect($strategy->apply($builder, 'desc'))->toBe($modifiedBuilder);
});
