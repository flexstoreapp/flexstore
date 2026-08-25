<?php

declare(strict_types=1);

use App\Enums\StorefrontSectionType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\StorefrontSection;
use App\Queries\ResolveSectionMediaQuery;
use App\Queries\ResolveSectionSettingsQuery;

covers(ResolveSectionSettingsQuery::class);

uses()->group('queries', 'storefront');

test('returns settings unchanged for non-resolving section types', function () {
    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::InfoStrip,
        'settings' => ['items' => []],
    ]);

    $result = app(ResolveSectionSettingsQuery::class)->execute($section, app(ResolveSectionMediaQuery::class)->preload(collect([$section])));

    expect($result)->toBe($section->settings);
});

test('returns settings unchanged when section has no settings', function () {
    $section = StorefrontSection::factory()->create(['settings' => null]);

    $result = app(ResolveSectionSettingsQuery::class)->execute($section, app(ResolveSectionMediaQuery::class)->preload(collect([$section])));

    expect($result)->toBe([]);
});

test('resolves category names for category grid section', function () {
    $category1 = Category::factory()->create(['name' => ['en' => 'Electronics', 'es' => 'Electrónica']]);
    $category2 = Category::factory()->create(['name' => ['en' => 'Clothing', 'es' => 'Ropa']]);
    $media = Media::factory()->create();

    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::CategoryGrid,
        'settings' => [
            'categories' => [
                ['category_id' => $category1->id, 'image' => $media->id, 'text_color' => 'light'],
                ['category_id' => $category2->id, 'image' => null, 'text_color' => 'dark'],
            ],
            'column_count' => 5,
        ],
    ]);

    $result = app(ResolveSectionSettingsQuery::class)->execute($section, app(ResolveSectionMediaQuery::class)->preload(collect([$section])));

    expect($result['column_count'])->toBe(5)
        ->and($result['categories'])->toHaveCount(2)
        ->and($result['categories'][0]['category_id'])->toBe($category1->id)
        ->and($result['categories'][0]['image']['id'])->toBe($media->id)
        ->and($result['categories'][0]['name'])->toBe(['en' => 'Electronics', 'es' => 'Electrónica'])
        ->and($result['categories'][0]['text_color'])->toBe('light')
        ->and($result['categories'][1]['image'])->toBeNull()
        ->and($result['categories'][1]['name'])->toBe(['en' => 'Clothing', 'es' => 'Ropa'])
        ->and($result['categories'][1]['text_color'])->toBe('dark');
});

test('returns settings unchanged when category grid has no categories', function () {
    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::CategoryGrid,
        'settings' => ['categories' => [], 'column_count' => 5],
    ]);

    $result = app(ResolveSectionSettingsQuery::class)->execute($section, app(ResolveSectionMediaQuery::class)->preload(collect([$section])));

    expect($result)->toBe($section->settings);
});

test('featured products section resolves category and product details', function () {
    $category = Category::factory()->create(['name' => ['en' => 'Gadgets']]);
    $product = Product::factory()->create(['title' => ['en' => 'Widget'], 'price' => '19.9900']);

    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::FeaturedProducts,
        'settings' => [
            'category_id' => $category->id,
            'product_ids' => [$product->id, 99999],
        ],
    ]);

    $result = app(ResolveSectionSettingsQuery::class)->execute($section, app(ResolveSectionMediaQuery::class)->preload(collect([$section])));

    expect($result['category']['id'])->toBe($category->id)
        ->and($result['category']['name'])->toBe(['en' => 'Gadgets'])
        ->and($result['products'])->toHaveCount(1)
        ->and($result['products'][0]['id'])->toBe($product->id)
        ->and($result['products'][0]['title'])->toBe(['en' => 'Widget'])
        ->and($result['products'][0]['price'])->toBe('19.9900')
        ->and($result['products'][0]['type'])->toBe('physical');
});

test('featured products section leaves settings untouched when category id does not match', function () {
    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::FeaturedProducts,
        'settings' => ['category_id' => 99999],
    ]);

    $result = app(ResolveSectionSettingsQuery::class)->execute($section, app(ResolveSectionMediaQuery::class)->preload(collect([$section])));

    expect($result)->not->toHaveKey('category');
});

test('product carousel section resolves product details', function () {
    $product = Product::factory()->create(['title' => ['en' => 'Carousel Product'], 'price' => '29.9900']);

    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::ProductCarousel,
        'settings' => ['product_ids' => [$product->id]],
    ]);

    $result = app(ResolveSectionSettingsQuery::class)->execute($section, app(ResolveSectionMediaQuery::class)->preload(collect([$section])));

    expect($result['products'])->toHaveCount(1)
        ->and($result['products'][0]['id'])->toBe($product->id);
});

test('product tabs section resolves products within each tab', function () {
    $product = Product::factory()->create(['title' => ['en' => 'Tab Product']]);

    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::ProductTabs,
        'settings' => [
            'tabs' => [
                ['label' => ['en' => 'Trending'], 'product_ids' => [$product->id]],
                ['label' => ['en' => 'Popular'], 'product_ids' => []],
            ],
        ],
    ]);

    $result = app(ResolveSectionSettingsQuery::class)->execute($section, app(ResolveSectionMediaQuery::class)->preload(collect([$section])));

    expect($result['tabs'][0]['products'])->toHaveCount(1)
        ->and($result['tabs'][0]['products'][0]['id'])->toBe($product->id)
        ->and($result['tabs'][1])->not->toHaveKey('products');
});

test('product columns section resolves products within each column', function () {
    $product = Product::factory()->create(['title' => ['en' => 'Column Product']]);

    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::ProductColumns,
        'settings' => [
            'columns' => [
                ['heading' => ['en' => 'Top rated'], 'product_ids' => [$product->id]],
            ],
        ],
    ]);

    $result = app(ResolveSectionSettingsQuery::class)->execute($section, app(ResolveSectionMediaQuery::class)->preload(collect([$section])));

    expect($result['columns'][0]['products'])->toHaveCount(1)
        ->and($result['columns'][0]['products'][0]['id'])->toBe($product->id);
});

test('brand strip resolves brand details', function () {
    $brand1 = Brand::factory()->create(['name' => ['en' => 'Brand One']]);
    $brand2 = Brand::factory()->create(['name' => ['en' => 'Brand Two']]);

    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::BrandStrip,
        'settings' => ['brand_ids' => [$brand1->id, 99999, $brand2->id]],
    ]);

    $result = app(ResolveSectionSettingsQuery::class)->execute($section, app(ResolveSectionMediaQuery::class)->preload(collect([$section])));

    expect($result['brands'])->toHaveCount(2)
        ->and($result['brands'][0]['id'])->toBe($brand1->id)
        ->and($result['brands'][0]['name'])->toBe(['en' => 'Brand One'])
        ->and($result['brands'][1]['id'])->toBe($brand2->id);
});

test('brand strip returns settings unchanged when brand_ids is empty', function () {
    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::BrandStrip,
        'settings' => ['brand_ids' => []],
    ]);

    $result = app(ResolveSectionSettingsQuery::class)->execute($section, app(ResolveSectionMediaQuery::class)->preload(collect([$section])));

    expect($result)->not->toHaveKey('brands');
});
