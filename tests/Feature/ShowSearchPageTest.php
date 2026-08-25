<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\SearchController;
use App\Http\Requests\Storefront\ProductFilterRequest;
use App\Models\Product;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers([
    SearchController::class,
    ProductFilterRequest::class,
]);

uses()->group('search-page');

test('displays search page', function () {
    Product::factory()->count(3)->active()->create();

    $response = get(route('search'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page->component('storefront/shop/list')
        );
});

test('defaults to relevance sort and ranks title matches first', function () {
    $exact = Product::factory()->active()->create(['title' => ['en' => 'Laptop']]);
    $startsWith = Product::factory()->active()->create(['title' => ['en' => 'Laptop Pro']]);
    $contains = Product::factory()->active()->create(['title' => ['en' => 'Gaming Laptop']]);

    get(route('search', ['query' => 'laptop']))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('filters.sort', 'relevance')
                ->where('products.data.0.id', $exact->id)
                ->where('products.data.1.id', $startsWith->id)
                ->where('products.data.2.id', $contains->id)
        );
});

test('exposes searchQuery for the shop page', function () {
    get(route('search', ['query' => 'shirt']))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('searchQuery', 'shirt'));
});

test('rejects a query longer than 100 characters', function () {
    get(route('search', ['query' => str_repeat('a', 101)]))
        ->assertInvalid(['query']);
});

test('rejects a non-integer page', function () {
    get(route('search', ['query' => 'shirt', 'page' => 'abc']))
        ->assertInvalid(['page']);
});

test('rejects a page below 1', function () {
    get(route('search', ['query' => 'shirt', 'page' => 0]))
        ->assertInvalid(['page']);
});
