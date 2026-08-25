<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\User;
use App\Queries\ShippingRateListQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

covers(ShippingRateListQuery::class);

uses()->group('queries', 'shipping');

test('returns empty collection when no shipping rates exist', function (): void {
    actingAsSuperAdmin();

    $query = app(ShippingRateListQuery::class);
    $result = $query->execute();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(0);
});

test('returns shipping rates with region and carrier loaded', function (): void {
    actingAsSuperAdmin();

    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
    ]);

    $query = app(ShippingRateListQuery::class);
    $result = $query->execute();

    /** @var ShippingRate $fetched */
    $fetched = $result->items()[0];

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(1)
        ->and($fetched->id)->toBe($shippingRate->id)
        ->and($fetched->relationLoaded('region'))->toBeTrue()
        ->and($fetched->relationLoaded('carrier'))->toBeTrue()
        ->and($fetched->region?->id)->toBe($region->id)
        ->and($fetched->carrier?->id)->toBe($carrier->id);
});

test('paginates shipping rates with hydrated relations', function (): void {
    actingAsSuperAdmin();

    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $product = Product::factory()->create();
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();

    ShippingRate::factory()->create([
        'name' => 'Priority',
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'excluded_products' => [$product->id],
        'excluded_categories' => [$category->id],
        'excluded_brands' => [$brand->id],
    ]);

    ShippingRate::factory()->create([
        'name' => 'Economy',
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
    ]);

    $query = app(ShippingRateListQuery::class);
    $paginator = $query->execute(perPage: 1);

    expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($paginator->total())->toBe(2)
        ->and($paginator->perPage())->toBe(1)
        ->and($paginator->currentPage())->toBe(1);

    /** @var ShippingRate $shippingRate */
    $shippingRate = $paginator->items()[0];

    expect($shippingRate->relationLoaded('region'))->toBeTrue()
        ->and($shippingRate->relationLoaded('carrier'))->toBeTrue()
        ->and($shippingRate->excluded_products)->toHaveCount(1)
        ->and($shippingRate->excluded_products[0])->toHaveKeys(['id', 'title', 'price', 'featured_media'])
        ->and($shippingRate->excluded_categories)->toHaveCount(1)
        ->and($shippingRate->excluded_categories[0])->toHaveKeys(['id', 'name'])
        ->and($shippingRate->excluded_brands)->toHaveCount(1)
        ->and($shippingRate->excluded_brands[0])->toHaveKeys(['id', 'name']);
});

test('eager loads excluded product media leanly', function (): void {
    $product = Product::factory()->withMedia(5)->create();

    ShippingRate::factory()->create([
        'excluded_products' => [$product->id],
        'excluded_categories' => [],
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    app(ShippingRateListQuery::class)->execute();
    $mediaQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->map(fn (string $sql): string => loggedSql($sql))
        ->filter(fn (string $sql): bool => str_contains($sql, 'from media'));
    DB::flushQueryLog();
    DB::disableQueryLog();

    expect($mediaQueries)->not->toBeEmpty()
        ->and($mediaQueries->every(fn (string $sql): bool => ! str_contains($sql, 'media.*')))->toBeTrue();
});

test('populates excluded products with correct data', function (): void {
    actingAsSuperAdmin();

    $region = Region::factory()->create();
    $product1 = Product::factory()->create(['title' => 'Product 1', 'price' => '10.0000']);
    $product2 = Product::factory()->create(['title' => 'Product 2', 'price' => '20.0000']);

    ShippingRate::factory()->create([
        'region_id' => $region->id,
        'excluded_products' => [$product1->id, $product2->id],
        'excluded_categories' => [],
    ]);

    $query = app(ShippingRateListQuery::class);
    $result = $query->execute();

    /** @var ShippingRate $shippingRate */
    $shippingRate = $result->items()[0];
    expect($shippingRate->excluded_products)->toHaveCount(2)
        ->and(collect($shippingRate->excluded_products)->pluck('id')->toArray())->toBe([$product1->id, $product2->id])
        ->and($shippingRate->excluded_products[0])->toHaveKeys(['id', 'title', 'price', 'featured_media'])
        ->and($shippingRate->excluded_products[0])->not->toHaveKey('images');
});

test('populates excluded categories with correct data', function (): void {
    actingAsSuperAdmin();

    $region = Region::factory()->create();
    $category1 = Category::factory()->create(['name' => 'Category 1']);
    $category2 = Category::factory()->create(['name' => 'Category 2']);

    ShippingRate::factory()->create([
        'region_id' => $region->id,
        'excluded_products' => [],
        'excluded_categories' => [$category1->id, $category2->id],
    ]);

    $query = app(ShippingRateListQuery::class);
    $result = $query->execute();

    /** @var ShippingRate $shippingRate */
    $shippingRate = $result->items()[0];
    expect($shippingRate->excluded_categories)->toHaveCount(2)
        ->and(collect($shippingRate->excluded_categories)->pluck('id')->toArray())->toBe([$category1->id, $category2->id])
        ->and(collect($shippingRate->excluded_categories)->pluck('name')->toArray())->toBe([castAsTranslatableArray('Category 1'), castAsTranslatableArray('Category 2')])
        ->and($shippingRate->excluded_categories[0])->toHaveKeys(['id', 'name']);
});

test('populates excluded brands with correct data', function (): void {
    actingAsSuperAdmin();

    $region = Region::factory()->create();
    $brand1 = Brand::factory()->create(['name' => 'Brand 1']);
    $brand2 = Brand::factory()->create(['name' => 'Brand 2']);

    ShippingRate::factory()->create([
        'region_id' => $region->id,
        'excluded_products' => [],
        'excluded_categories' => [],
        'excluded_brands' => [$brand1->id, $brand2->id],
    ]);

    $query = app(ShippingRateListQuery::class);
    $result = $query->execute();

    /** @var ShippingRate $shippingRate */
    $shippingRate = $result->items()[0];
    expect($shippingRate->excluded_brands)->toHaveCount(2)
        ->and(collect($shippingRate->excluded_brands)->pluck('id')->toArray())->toBe([$brand1->id, $brand2->id])
        ->and(collect($shippingRate->excluded_brands)->pluck('name')->toArray())->toBe([castAsTranslatableArray('Brand 1'), castAsTranslatableArray('Brand 2')])
        ->and($shippingRate->excluded_brands[0])->toHaveKeys(['id', 'name']);
});

test('ignores non-existent excluded ids', function (): void {
    actingAsSuperAdmin();

    $region = Region::factory()->create();
    $product = Product::factory()->create();
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();

    ShippingRate::factory()->create([
        'region_id' => $region->id,
        'excluded_products' => [$product->id, 999],
        'excluded_categories' => [$category->id, 1000],
        'excluded_brands' => [$brand->id, 2000],
    ]);

    $query = app(ShippingRateListQuery::class);
    $result = $query->execute();

    /** @var ShippingRate $shippingRate */
    $shippingRate = $result->items()[0];
    expect($shippingRate->excluded_products)->toHaveCount(1)
        ->and($shippingRate->excluded_products[0]['id'])->toBe($product->id)
        ->and($shippingRate->excluded_categories)->toHaveCount(1)
        ->and($shippingRate->excluded_categories[0]['id'])->toBe($category->id)
        ->and($shippingRate->excluded_brands)->toHaveCount(1)
        ->and($shippingRate->excluded_brands[0]['id'])->toBe($brand->id);
});

test('returns empty collection when user lacks required permissions', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Region::factory(3)->create();

    $query = app(ShippingRateListQuery::class);
    $result = $query->execute();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(0);
});

test('filters shipping rates by search query', function (): void {
    actingAsSuperAdmin();

    $region = Region::factory()->create(['name' => 'North']);
    $carrier = ShippingCarrier::factory()->create(['name' => 'Fast Ship']);

    ShippingRate::factory()->create([
        'name' => 'Express',
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
    ]);

    ShippingRate::factory()->create([
        'name' => 'Standard',
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
    ]);

    $query = app(ShippingRateListQuery::class);
    $result = $query->execute(['query' => 'Express']);

    /** @var ShippingRate $shippingRate */
    $shippingRate = $result->items()[0];

    expect($result->total())->toBe(1)
        ->and($shippingRate->name)->toBe('Express');
});
