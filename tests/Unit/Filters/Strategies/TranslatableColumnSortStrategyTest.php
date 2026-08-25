<?php

declare(strict_types=1);

use App\Filters\Strategies\TranslatableColumnSortStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

covers(TranslatableColumnSortStrategy::class);

uses()->group('filters');

test('apply forwards driver-specific orderByRaw expression', function () {
    DB::shouldReceive('getDriverName')->andReturn('pgsql');
    app()->setLocale('en');

    $builder = Mockery::mock(Builder::class);
    $returned = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderByRaw')
        ->with('name->>? asc', ['en'])
        ->once()
        ->andReturn($returned);

    expect((new TranslatableColumnSortStrategy('name'))->apply($builder, 'asc'))
        ->toBe($returned);
});

test('apply forwards desc direction', function () {
    DB::shouldReceive('getDriverName')->andReturn('mysql');
    app()->setLocale('bn');

    $builder = Mockery::mock(Builder::class);
    $returned = Mockery::mock(Builder::class);

    $builder->shouldReceive('orderByRaw')
        ->with('coalesce(JSON_UNQUOTE(JSON_EXTRACT(brands.name, ?)), JSON_UNQUOTE(JSON_EXTRACT(brands.name, ?))) desc', ['$.bn', '$.en'])
        ->once()
        ->andReturn($returned);

    expect((new TranslatableColumnSortStrategy('brands.name'))->apply($builder, 'desc'))
        ->toBe($returned);
});
