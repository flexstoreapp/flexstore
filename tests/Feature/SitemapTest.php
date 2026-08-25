<?php

declare(strict_types=1);

use App\Http\Controllers\SitemapController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Queries\SitemapQuery;

use function Pest\Laravel\get;

covers(SitemapController::class, SitemapQuery::class);

uses()->group('seo', 'sitemap');

test('renders a valid xml sitemap', function () {
    $response = get('/sitemap.xml');

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('xml');

    $response->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false)
        ->assertSee('<urlset', false)
        ->assertSee(route('home'), false);
});

test('excludes inactive products, categories and brands', function () {
    $product = Product::factory()->inactive()->create(['url_handle' => 'hidden-product']);
    $category = Category::factory()->inactive()->create(['url_handle' => 'hidden-category']);
    $brand = Brand::factory()->inactive()->create(['url_handle' => 'hidden-brand']);

    $response = get('/sitemap.xml');

    $response->assertOk()
        ->assertDontSee(route('products.show', $product->url_handle), false)
        ->assertDontSee(route('categories.products.show', $category->url_handle), false)
        ->assertDontSee(route('brands.products.show', $brand->url_handle), false);
});
