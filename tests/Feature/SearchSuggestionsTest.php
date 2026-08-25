<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\SearchSuggestionController;
use App\Http\Requests\Storefront\SearchSuggestionRequest;
use App\Models\Product;
use App\Queries\ProductSearchSuggestionsQuery;

use function Pest\Laravel\getJson;

covers(SearchSuggestionController::class, SearchSuggestionRequest::class, ProductSearchSuggestionsQuery::class);

uses()->group('search');

test('returns empty suggestions for empty query', function () {
    $response = getJson(route('search.suggestions'));

    $response->assertOk()
        ->assertJson([
            'suggestions' => [],
            'total' => 0,
            'has_more' => false,
            'query' => '',
        ]);
});

test('returns empty suggestions for query shorter than minimum', function () {
    Product::factory()->create(['title' => ['en' => 'Test Product'], 'is_active' => true]);

    $response = getJson(route('search.suggestions', ['query' => 'a']));

    $response->assertOk()
        ->assertJson([
            'suggestions' => [],
            'total' => 0,
            'has_more' => false,
            'query' => 'a',
        ]);
});

test('returns matching products for valid query', function () {
    $product = Product::factory()->create([
        'title' => ['en' => 'Blue T-Shirt'],
        'is_active' => true,
        'in_stock' => true,
        'price' => 29.99,
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'blue']));

    $response->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('query', 'blue')
        ->assertJsonPath('suggestions.0.id', $product->id)
        ->assertJsonPath('suggestions.0.url_handle', $product->url_handle)
        ->assertJsonPath('suggestions.0.title.en', 'Blue T-Shirt')
        ->assertJsonPath('suggestions.0.in_stock', true);
});

test('returns product category name', function () {
    $category = App\Models\Category::factory()->create(['name' => ['en' => 'Apparel']]);

    Product::factory()->create([
        'title' => ['en' => 'Blue T-Shirt'],
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'blue']));

    $response->assertOk()
        ->assertJsonPath('suggestions.0.category.en', 'Apparel');
});

test('returns product featured media', function () {
    $product = Product::factory()->withMedia(1)->create([
        'title' => ['en' => 'Test Product'],
        'is_active' => true,
    ]);
    $media = $product->load('mediaGallery')->mediaGallery->first();

    $response = getJson(route('search.suggestions', ['query' => 'test']));

    $response->assertOk()
        ->assertJsonPath('suggestions.0.featured_media.id', $media->id)
        ->assertJsonPath('suggestions.0.featured_media.thumbnail_url', $media->thumbnail_url);
});

test('limits results to 5 products', function () {
    Product::factory()->count(8)->create([
        'title' => ['en' => 'Test Product'],
        'is_active' => true,
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'test']));

    $response->assertOk()
        ->assertJsonCount(5, 'suggestions')
        ->assertJsonPath('total', 8)
        ->assertJsonPath('has_more', true);
});

test('indicates no more results when under limit', function () {
    Product::factory()->count(5)->create([
        'title' => ['en' => 'Test Product'],
        'is_active' => true,
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'test']));

    $response->assertOk()
        ->assertJsonCount(5, 'suggestions')
        ->assertJsonPath('total', 5)
        ->assertJsonPath('has_more', false);
});

test('excludes inactive products', function () {
    Product::factory()->create([
        'title' => ['en' => 'Active Product'],
        'is_active' => true,
    ]);

    Product::factory()->create([
        'title' => ['en' => 'Inactive Product'],
        'is_active' => false,
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'product']));

    $response->assertOk()
        ->assertJsonCount(1, 'suggestions')
        ->assertJsonPath('suggestions.0.title.en', 'Active Product');
});

test('searches by sku', function () {
    $product = Product::factory()->create([
        'title' => ['en' => 'Some Product'],
        'sku' => 'ABC123',
        'is_active' => true,
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'ABC123']));

    $response->assertOk()
        ->assertJsonCount(1, 'suggestions')
        ->assertJsonPath('suggestions.0.id', $product->id);
});

test('search is case insensitive', function () {
    Product::factory()->create([
        'title' => ['en' => 'Blue T-Shirt'],
        'is_active' => true,
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'BLUE']));

    $response->assertOk()
        ->assertJsonCount(1, 'suggestions')
        ->assertJsonPath('suggestions.0.title.en', 'Blue T-Shirt');
});

test('returns product price', function () {
    Product::factory()->create([
        'title' => ['en' => 'Test Product'],
        'is_active' => true,
        'price' => 99.99,
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'test']));

    $response->assertOk()
        ->assertJsonPath('suggestions.0.price', '99.9900');
});

test('returns in_stock status', function () {
    Product::factory()->create([
        'title' => ['en' => 'Out of Stock Product'],
        'is_active' => true,
        'in_stock' => false,
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'stock']));

    $response->assertOk()
        ->assertJsonPath('suggestions.0.in_stock', false);
});

test('trims whitespace from query', function () {
    Product::factory()->create([
        'title' => ['en' => 'Test Product'],
        'is_active' => true,
    ]);

    $response = getJson(route('search.suggestions', ['query' => '  test  ']));

    $response->assertOk()
        ->assertJsonPath('query', 'test')
        ->assertJsonCount(1, 'suggestions');
});

test('returns in_stock true when product has variants and at least one variant is in stock', function () {
    $product = Product::factory()->create([
        'title' => ['en' => 'Product with Variants'],
        'is_active' => true,
        'in_stock' => null,
    ]);

    $product->variants()->createMany([
        ['title' => ['en' => 'Variant 1'], 'price' => 29.99, 'in_stock' => false, 'track_stock' => true],
        ['title' => ['en' => 'Variant 2'], 'price' => 39.99, 'in_stock' => true, 'track_stock' => true],
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'variants']));

    $response->assertOk()
        ->assertJsonPath('suggestions.0.in_stock', true);
});

test('returns in_stock false when product has variants and all variants are out of stock', function () {
    $product = Product::factory()->create([
        'title' => ['en' => 'Product with Variants'],
        'is_active' => true,
        'in_stock' => null,
    ]);

    $product->variants()->createMany([
        ['title' => ['en' => 'Variant 1'], 'price' => 29.99, 'in_stock' => false, 'track_stock' => true],
        ['title' => ['en' => 'Variant 2'], 'price' => 39.99, 'in_stock' => false, 'track_stock' => true],
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'variants']));

    $response->assertOk()
        ->assertJsonPath('suggestions.0.in_stock', false);
});

test('returns base product in_stock when product has no variants', function () {
    Product::factory()->create([
        'title' => ['en' => 'Simple Product'],
        'is_active' => true,
        'in_stock' => true,
    ]);

    $response = getJson(route('search.suggestions', ['query' => 'simple']));

    $response->assertOk()
        ->assertJsonPath('suggestions.0.in_stock', true);
});
