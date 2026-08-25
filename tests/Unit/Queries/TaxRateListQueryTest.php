<?php

declare(strict_types=1);

use App\Enums\TaxCategory;
use App\Models\Region;
use App\Models\TaxRate;
use App\Queries\TaxRateListQuery;
use Illuminate\Pagination\LengthAwarePaginator;

covers(TaxRateListQuery::class);

uses()->group('queries', 'tax');

test('returns paginated tax rates', function () {
    TaxRate::factory()->count(20)->create();

    $query = app(TaxRateListQuery::class);
    $result = $query->execute(perPage: 15);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(15)
        ->and($result->total())->toBe(20);
});

test('returns empty paginator when no tax rates exist', function () {
    $query = app(TaxRateListQuery::class);
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('loads region relationship', function () {
    $region = Region::factory()->create();
    TaxRate::factory()->create(['region_id' => $region->id]);

    $query = app(TaxRateListQuery::class);
    $result = $query->execute();

    expect($result->first()->relationLoaded('region'))->toBeTrue()
        ->and($result->first()->region->id)->toBe($region->id);
});

test('casts tax category to enum', function () {
    TaxRate::factory()->create(['tax_category' => TaxCategory::Electronics]);

    $query = app(TaxRateListQuery::class);
    $result = $query->execute();

    expect($result->first()->tax_category)->toBe(TaxCategory::Electronics);
});

test('respects per page parameter', function () {
    TaxRate::factory()->count(10)->create();

    $query = app(TaxRateListQuery::class);
    $result = $query->execute(perPage: 5);

    expect($result)->toHaveCount(5)
        ->and($result->total())->toBe(10);
});
