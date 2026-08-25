<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\BrandProductController;
use App\Models\Brand;
use App\Models\Product;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers(BrandProductController::class);

uses()->group('brand-page');

test('displays brand page', function () {
    $brand = Brand::factory()->active()->create();
    Product::factory()->count(3)->active()->create(['brand_id' => $brand->id]);

    $response = get(route('brands.products.show', $brand->url_handle));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page->component('storefront/shop/list')
        );
});

test('uses the brand seo description as meta description', function () {
    $brand = Brand::factory()->active()->create(['seo_description' => ['en' => 'Official brand store.']]);

    get(route('brands.products.show', $brand->url_handle))
        ->assertOk()
        ->assertSee('name="description"', false)
        ->assertSee('content="Official brand store."', false);
});

test('falls back to the brand description when there is no seo description', function () {
    $brand = Brand::factory()->active()->create([
        'seo_description' => null,
        'description' => ['en' => '<p>The <em>official</em> brand store.</p>'],
    ]);

    get(route('brands.products.show', $brand->url_handle))
        ->assertOk()
        ->assertSee('name="description"', false)
        ->assertSee('content="The official brand store."', false);
});

test('passes the brand description to the page', function () {
    $brand = Brand::factory()->active()->create(['description' => ['en' => 'Crafted since 1902.']]);

    get(route('brands.products.show', $brand->url_handle))
        ->assertInertia(
            fn (AssertableInertia $page) => $page->where('context.description.en', 'Crafted since 1902.')
        );
});

test('returns 404 for inactive brand', function () {
    $brand = Brand::factory()->inactive()->create();

    $response = get(route('brands.products.show', $brand->url_handle));

    $response->assertNotFound();
});
