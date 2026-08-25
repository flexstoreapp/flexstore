<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Category;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use App\Queries\PaymentGatewayListQuery;
use Illuminate\Support\Facades\DB;

covers(PaymentGatewayListQuery::class);

uses()->group('queries', 'payment');

test('returns all payment gateways', function () {
    PaymentGateway::factory()->stripe()->create();
    PaymentGateway::factory()->paypal()->create();
    PaymentGateway::factory()->cod()->create();

    $query = app(PaymentGatewayListQuery::class);
    $result = $query->execute();

    expect($result)->toHaveCount(3);
});

test('returns empty collection when no gateways exist', function () {
    $query = app(PaymentGatewayListQuery::class);
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('makes credentials visible', function () {
    PaymentGateway::factory()->create();

    $query = app(PaymentGatewayListQuery::class);
    $result = $query->execute();

    $gatewayArray = $result->first()->toArray();

    expect($gatewayArray)->toHaveKey('credentials');
});

test('populates excluded products with correct data', function () {
    $product = Product::factory()->create();
    PaymentGateway::factory()->create([
        'excluded_products' => [$product->id],
        'excluded_categories' => [],
        'allowed_regions' => [],
    ]);

    $query = app(PaymentGatewayListQuery::class);
    $result = $query->execute();

    expect($result->first()->excluded_products)->toHaveCount(1)
        ->and($result->first()->excluded_products[0])->toHaveKeys(['id', 'title', 'price', 'featured_media']);
});

test('populates excluded categories with correct data', function () {
    $category = Category::factory()->create();
    PaymentGateway::factory()->create([
        'excluded_products' => [],
        'excluded_categories' => [$category->id],
        'allowed_regions' => [],
    ]);

    $query = app(PaymentGatewayListQuery::class);
    $result = $query->execute();

    expect($result->first()->excluded_categories)->toHaveCount(1)
        ->and($result->first()->excluded_categories[0])->toHaveKeys(['id', 'name']);
});

test('populates excluded brands with correct data', function () {
    $brand = Brand::factory()->create();
    PaymentGateway::factory()->create([
        'excluded_products' => [],
        'excluded_categories' => [],
        'excluded_brands' => [$brand->id],
        'allowed_regions' => [],
    ]);

    $query = app(PaymentGatewayListQuery::class);
    $result = $query->execute();

    expect($result->first()->excluded_brands)->toHaveCount(1)
        ->and($result->first()->excluded_brands[0])->toHaveKeys(['id', 'name']);
});

test('populates allowed regions with correct data', function () {
    $region = Region::factory()->create();
    PaymentGateway::factory()->create([
        'excluded_products' => [],
        'excluded_categories' => [],
        'allowed_regions' => [$region->id],
    ]);

    $query = app(PaymentGatewayListQuery::class);
    $result = $query->execute();

    expect($result->first()->allowed_regions)->toHaveCount(1)
        ->and($result->first()->allowed_regions[0])->toHaveKeys(['id', 'name']);
});

test('handles gateway with no exclusions or restrictions', function () {
    PaymentGateway::factory()->create([
        'excluded_products' => [],
        'excluded_categories' => [],
        'allowed_regions' => [],
    ]);

    $query = app(PaymentGatewayListQuery::class);
    $result = $query->execute();

    expect($result->first()->excluded_products)->toBeEmpty()
        ->and($result->first()->excluded_categories)->toBeEmpty()
        ->and($result->first()->excluded_brands)->toBeEmpty()
        ->and($result->first()->allowed_regions)->toBeEmpty();
});

test('eager loads excluded product media leanly', function () {
    $product = Product::factory()->withMedia(5)->create();
    PaymentGateway::factory()->create([
        'excluded_products' => [$product->id],
        'excluded_categories' => [],
        'allowed_regions' => [],
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    app(PaymentGatewayListQuery::class)->execute()->first()->toArray();
    $mediaQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->map(fn (string $sql): string => loggedSql($sql))
        ->filter(fn (string $sql): bool => str_contains($sql, 'from media'));
    DB::flushQueryLog();
    DB::disableQueryLog();

    expect($mediaQueries)->not->toBeEmpty()
        ->and($mediaQueries->every(fn (string $sql): bool => ! str_contains($sql, 'media.*')))->toBeTrue();
});

test('ignores non-existent excluded ids', function () {
    $product = Product::factory()->create();
    PaymentGateway::factory()->create([
        'excluded_products' => [$product->id, 999],
        'excluded_categories' => [1000],
        'excluded_brands' => [3000],
        'allowed_regions' => [2000],
    ]);

    $query = app(PaymentGatewayListQuery::class);
    $result = $query->execute();

    expect($result->first()->excluded_products)->toHaveCount(1)
        ->and($result->first()->excluded_categories)->toHaveCount(0)
        ->and($result->first()->excluded_brands)->toHaveCount(0)
        ->and($result->first()->allowed_regions)->toHaveCount(0);
});
