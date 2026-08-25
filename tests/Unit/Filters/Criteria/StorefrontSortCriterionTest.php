<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Enums\ProductSortOption;
use App\Filters\Criteria\StorefrontSortCriterion;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Mockery;

covers(StorefrontSortCriterion::class);

uses()->group('filters');

test('sorts by latest (created_at desc) by default', function () {
    $oldest = Product::factory()->create(['created_at' => now()->subDays(2)]);
    $newest = Product::factory()->create(['created_at' => now()]);
    $middle = Product::factory()->create(['created_at' => now()->subDay()]);

    $criterion = new StorefrontSortCriterion();
    $result = $criterion->apply(Product::query(), null)->get();

    expect($result->first()->id)->toBe($newest->id)
        ->and($result->last()->id)->toBe($oldest->id);
});

test('sorts by price low to high', function () {
    $expensive = Product::factory()->create(['price' => 100]);
    $cheap = Product::factory()->create(['price' => 10]);
    $medium = Product::factory()->create(['price' => 50]);

    $criterion = new StorefrontSortCriterion();
    $result = $criterion->apply(Product::query(), ProductSortOption::PriceLowHigh->value)->get();

    expect($result->first()->id)->toBe($cheap->id)
        ->and($result->last()->id)->toBe($expensive->id);
});

test('sorts by price high to low', function () {
    $expensive = Product::factory()->create(['price' => 100]);
    $cheap = Product::factory()->create(['price' => 10]);
    $medium = Product::factory()->create(['price' => 50]);

    $criterion = new StorefrontSortCriterion();
    $result = $criterion->apply(Product::query(), ProductSortOption::PriceHighLow->value)->get();

    expect($result->first()->id)->toBe($expensive->id)
        ->and($result->last()->id)->toBe($cheap->id);
});

test('sorts by name A to Z', function () {
    $productC = Product::factory()->create(['title' => ['en' => 'Charlie Product']]);
    $productA = Product::factory()->create(['title' => ['en' => 'Alpha Product']]);
    $productB = Product::factory()->create(['title' => ['en' => 'Beta Product']]);

    $criterion = new StorefrontSortCriterion();
    $result = $criterion->apply(Product::query(), ProductSortOption::NameAZ->value)->get();

    expect($result->first()->id)->toBe($productA->id)
        ->and($result->last()->id)->toBe($productC->id);
});

test('sorts by name Z to A', function () {
    $productC = Product::factory()->create(['title' => ['en' => 'Charlie Product']]);
    $productA = Product::factory()->create(['title' => ['en' => 'Alpha Product']]);
    $productB = Product::factory()->create(['title' => ['en' => 'Beta Product']]);

    $criterion = new StorefrontSortCriterion();
    $result = $criterion->apply(Product::query(), ProductSortOption::NameZA->value)->get();

    expect($result->first()->id)->toBe($productC->id)
        ->and($result->last()->id)->toBe($productA->id);
});

test('falls back to latest for invalid sort value', function () {
    $oldest = Product::factory()->create(['created_at' => now()->subDays(2)]);
    $newest = Product::factory()->create(['created_at' => now()]);

    $criterion = new StorefrontSortCriterion();
    $result = $criterion->apply(Product::query(), 'invalid_sort')->get();

    expect($result->first()->id)->toBe($newest->id)
        ->and($result->last()->id)->toBe($oldest->id);
});

test('canApply always returns true', function () {
    $criterion = new StorefrontSortCriterion();

    expect($criterion->canApply(null))->toBeTrue()
        ->and($criterion->canApply(''))->toBeTrue()
        ->and($criterion->canApply('latest'))->toBeTrue()
        ->and($criterion->canApply('invalid'))->toBeTrue();
});

test('name sort produces postgres expression when driver is pgsql', function () {
    DB::shouldReceive('getDriverName')->andReturn('pgsql');

    $builder = \Illuminate\Database\Eloquent\Builder::class;
    $mock = Mockery::mock($builder);
    $mock2 = Mockery::mock($builder);

    $mock->shouldReceive('orderByRaw')->with('title->>? asc', ['en'])->once()->andReturn($mock2);
    $mock2->shouldReceive('orderBy')->with('id')->once()->andReturnSelf();

    app()->setLocale('en');

    (new StorefrontSortCriterion())->apply($mock, ProductSortOption::NameAZ->value);
});

test('name sort produces postgres expression with desc for NameZA', function () {
    DB::shouldReceive('getDriverName')->andReturn('pgsql');

    $builder = \Illuminate\Database\Eloquent\Builder::class;
    $mock = Mockery::mock($builder);
    $mock2 = Mockery::mock($builder);

    $mock->shouldReceive('orderByRaw')->with('title->>? desc', ['en'])->once()->andReturn($mock2);
    $mock2->shouldReceive('orderBy')->with('id')->once()->andReturnSelf();

    app()->setLocale('en');

    (new StorefrontSortCriterion())->apply($mock, ProductSortOption::NameZA->value);
});

test('name sort produces mysql expression for non-sqlite/pgsql drivers', function () {
    DB::shouldReceive('getDriverName')->andReturn('mysql');

    $builder = \Illuminate\Database\Eloquent\Builder::class;
    $mock = Mockery::mock($builder);
    $mock2 = Mockery::mock($builder);

    $mock->shouldReceive('orderByRaw')->with('JSON_UNQUOTE(JSON_EXTRACT(title, ?)) desc', ['$.en'])->once()->andReturn($mock2);
    $mock2->shouldReceive('orderBy')->with('id')->once()->andReturnSelf();

    app()->setLocale('en');

    (new StorefrontSortCriterion())->apply($mock, ProductSortOption::NameZA->value);
});
