<?php

declare(strict_types=1);

use App\Enums\ReviewStatus;
use App\Http\Controllers\Storefront\ProductQuickViewController;
use App\Models\Product;
use App\Models\Review;
use App\Queries\ProductBuyBoxQuery;

use function Pest\Laravel\getJson;

covers([
    ProductQuickViewController::class,
    ProductBuyBoxQuery::class,
]);

uses()->group('storefront', 'product');

test('returns quick view payload for an active product', function () {
    $product = Product::factory()->available()->create();

    getJson(route('products.quick-view', $product->url_handle))
        ->assertOk()
        ->assertJsonPath('id', $product->id)
        ->assertJsonPath('url_handle', $product->url_handle)
        ->assertJsonStructure([
            'id', 'url_handle', 'title', 'sku', 'price', 'price_range', 'compare_at_price_range',
            'in_stock', 'max_quantity', 'rating', 'review_count', 'has_variants',
            'media', 'options', 'variants',
        ]);
});

test('exposes available stock as the max quantity when tracking stock', function () {
    $product = Product::factory()->active()->create([
        'track_stock' => true,
        'stock' => 7,
        'in_stock' => true,
    ]);

    getJson(route('products.quick-view', $product->url_handle))
        ->assertOk()
        ->assertJsonPath('max_quantity', 7);
});

test('max quantity is null when the product does not track stock', function () {
    $product = Product::factory()->active()->create([
        'track_stock' => false,
        'stock' => null,
        'in_stock' => true,
    ]);

    getJson(route('products.quick-view', $product->url_handle))
        ->assertOk()
        ->assertJsonPath('max_quantity', null);
});

test('includes options and variants for a variant product', function () {
    $product = Product::factory()->withVariants()->available()->create();

    $response = getJson(route('products.quick-view', $product->url_handle))->assertOk();

    expect($response->json('has_variants'))->toBeTrue()
        ->and($response->json('options'))->not->toBeEmpty()
        ->and($response->json('variants'))->not->toBeEmpty();

    $variant = $response->json('variants.0');
    expect($variant)->toHaveKeys(['id', 'title', 'price', 'in_stock', 'is_default', 'max_quantity', 'option_values']);
});

test('flags the default variant so the storefront can preselect it', function () {
    $product = Product::factory()->withVariants()->available()->create();
    $product->variants()->update(['is_default' => false]);
    $default = $product->variants()->first();
    $default->update(['is_default' => true]);

    $response = getJson(route('products.quick-view', $product->url_handle))->assertOk();

    $payload = collect($response->json('variants'));
    expect($payload->firstWhere('id', $default->id)['is_default'])->toBeTrue()
        ->and($payload->where('is_default', true))->toHaveCount(1);
});

test('exposes per-variant available stock as the variant max quantity', function () {
    $product = Product::factory()->withVariants()->available()->create();
    $variant = $product->variants()->first();
    $variant->update(['track_stock' => true, 'stock' => 4, 'in_stock' => true]);

    $response = getJson(route('products.quick-view', $product->url_handle))->assertOk();

    $payload = collect($response->json('variants'))->firstWhere('id', $variant->id);
    expect($payload['max_quantity'])->toBe(4);
});

test('aggregates only approved reviews into rating and review count', function () {
    $product = Product::factory()->available()->create();

    Review::factory()->for($product)->create(['rating' => 4, 'status' => ReviewStatus::Approved]);
    Review::factory()->for($product)->create(['rating' => 2, 'status' => ReviewStatus::Approved]);
    Review::factory()->for($product)->create(['rating' => 5, 'status' => ReviewStatus::Pending]);

    getJson(route('products.quick-view', $product->url_handle))
        ->assertOk()
        ->assertJsonPath('review_count', 2)
        ->assertJsonPath('rating', 3);
});

test('returns 404 for an inactive product', function () {
    $product = Product::factory()->inactive()->create();

    getJson(route('products.quick-view', $product->url_handle))->assertNotFound();
});

test('exposes the variant compare at price range for a variant product priced only on its variants', function () {
    $product = Product::factory()->active()->create([
        'price' => null,
        'compare_at_price' => null,
    ]);

    $product->variants()->createMany([
        ['title' => ['en' => 'UK 7'], 'price' => 184.99, 'compare_at_price' => 230.99, 'in_stock' => true, 'track_stock' => false],
        ['title' => ['en' => 'UK 8'], 'price' => 184.99, 'compare_at_price' => 230.99, 'in_stock' => true, 'track_stock' => false],
    ]);

    getJson(route('products.quick-view', $product->url_handle))
        ->assertOk()
        ->assertJsonPath('price', null)
        ->assertJsonPath('price_range', ['184.9900', '184.9900'])
        ->assertJsonPath('compare_at_price_range', ['230.9900', '230.9900']);
});
