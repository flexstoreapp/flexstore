<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\CategoryProductController;
use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers(CategoryProductController::class);

uses()->group('category-page');

test('displays category page', function () {
    $category = Category::factory()->active()->create();
    Product::factory()->count(3)->active()->create(['category_id' => $category->id]);

    $response = get(route('categories.products.show', $category->url_handle));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page->component('storefront/shop/list')
        );
});

test('uses the category seo description as meta description', function () {
    $category = Category::factory()->active()->create(['seo_description' => ['en' => 'Shop the latest gadgets.']]);

    get(route('categories.products.show', $category->url_handle))
        ->assertOk()
        ->assertSee('name="description"', false)
        ->assertSee('content="Shop the latest gadgets."', false);
});

test('falls back to the category description when there is no seo description', function () {
    $category = Category::factory()->active()->create([
        'seo_description' => null,
        'description' => ['en' => '<p>Discover our <strong>electronics</strong> range.</p>'],
    ]);

    get(route('categories.products.show', $category->url_handle))
        ->assertOk()
        ->assertSee('name="description"', false)
        ->assertSee('content="Discover our electronics range."', false);
});

test('passes the category description to the page', function () {
    $category = Category::factory()->active()->create(['description' => ['en' => 'Everything electronic.']]);

    get(route('categories.products.show', $category->url_handle))
        ->assertInertia(
            fn (AssertableInertia $page) => $page->where('context.description.en', 'Everything electronic.')
        );
});

test('returns 404 for inactive category', function () {
    $category = Category::factory()->inactive()->create();

    $response = get(route('categories.products.show', $category->url_handle));

    $response->assertNotFound();
});
