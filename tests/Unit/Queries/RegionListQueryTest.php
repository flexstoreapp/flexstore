<?php

declare(strict_types=1);

use App\Models\Region;
use App\Queries\RegionListQuery;
use Illuminate\Pagination\LengthAwarePaginator;

covers(RegionListQuery::class);

uses()->group('queries', 'region');

test('returns paginated regions', function () {
    Region::factory()->count(20)->create();

    $query = app(RegionListQuery::class);
    $result = $query->execute(perPage: 15);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(15)
        ->and($result->total())->toBe(20);
});

test('returns empty paginator when no regions exist', function () {
    $query = app(RegionListQuery::class);
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('respects per page parameter', function () {
    Region::factory()->count(10)->create();

    $query = app(RegionListQuery::class);
    $result = $query->execute(perPage: 5);

    expect($result)->toHaveCount(5)
        ->and($result->total())->toBe(10);
});

test('defaults to 15 per page', function () {
    Region::factory()->count(20)->create();

    $query = app(RegionListQuery::class);
    $result = $query->execute();

    expect($result)->toHaveCount(15)
        ->and($result->perPage())->toBe(15);
});
