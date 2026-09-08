<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;

use function Pest\Laravel\getJson;

uses()->group('api');

test('a single record is the response body itself', function (): void {
    $product = Product::factory()->active()->create();

    $body = getJson(route('api.v1.products.show', $product->url_handle))->assertOk()->json();

    expect($body)->toHaveKey('id')
        ->and($body)->not->toHaveKey('data')
        ->and($body['id'])->toBe($product->id);
});

test('a paginated list keeps the envelope so links and meta have somewhere to live', function (): void {
    Product::factory()->active()->create();

    getJson(route('api.v1.shop.index'))
        ->assertOk()
        ->assertJsonStructure(['data', 'links' => ['first', 'last', 'prev', 'next'], 'meta' => ['current_page', 'per_page', 'total']]);
});

test('an unpaginated list is a plain array', function (): void {
    Category::factory()->active()->create();

    $body = getJson(route('api.v1.categories.index'))->assertOk()->json();

    expect($body)->toBeArray()
        ->and(array_is_list($body))->toBeTrue();
});

test('a response with a sibling of its own keeps the envelope', function (): void {
    Product::factory()->active()->create(['title' => ['en' => 'Merino crew knit']]);

    getJson(route('api.v1.search.suggestions', ['query' => 'Merino']))
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['total', 'has_more', 'query']]);
});

test('a single record nested inside a manual payload is not wrapped again', function (): void {
    $config = getJson(route('api.v1.config'))->assertOk()->json();

    expect($config)->toHaveKeys(['store', 'locales', 'currencies'])
        ->and($config)->not->toHaveKey('data');
});
