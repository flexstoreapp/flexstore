<?php

declare(strict_types=1);

use App\Enums\ProductSource;
use App\Enums\StorefrontSectionType;
use App\Models\Category;
use App\Models\Media;
use App\Models\StorefrontSection;

uses()->group('storefront');

test('creates featured products section with specific settings', function () {
    $category = Category::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::FeaturedProducts->value,
        'title' => 'Featured',
        'settings' => [
            'product_source' => ProductSource::Category->value,
            'product_limit' => 12,
            'category_id' => $category->id,
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Featured'))->first();
    expect($section->settings)->toBeArray()
        ->and($section->settings['product_source'])->toBe(ProductSource::Category->value)
        ->and($section->settings['product_limit'])->toBe(12)
        ->and($section->settings['category_id'])->toBe($category->id);

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('creates category grid section with categories', function () {
    $category = Category::factory()->create();
    $media = Media::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::CategoryGrid->value,
        'title' => 'Shop by category',
        'settings' => [
            'categories' => [
                ['category_id' => $category->id, 'image' => $media->id],
            ],
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Shop by category'))->first();
    expect($section->settings['categories'][0]['category_id'])->toBe($category->id)
        ->and($section->settings['categories'][0]['image'])->toBe($media->id);

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('can update section type and settings', function () {
    $section = StorefrontSection::factory()->featuredProducts()->create([
        'title' => 'Old',
    ]);

    $category = Category::factory()->create();

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.homepage.sections.edit', $section))
        ->patch(route('admin.storefront.homepage.sections.update', $section), [
            'type' => StorefrontSectionType::ProductCarousel->value,
            'title' => 'Now a carousel',
            'settings' => [
                'product_source' => ProductSource::Category->value,
                'product_limit' => 20,
                'category_id' => $category->id,
            ],
        ]);

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();

    $section->refresh();
    expect($section->type)->toBe(StorefrontSectionType::ProductCarousel)
        ->and($section->title)->toBe('Now a carousel')
        ->and($section->settings['product_source'])->toBe(ProductSource::Category->value)
        ->and($section->settings['product_limit'])->toBe(20)
        ->and($section->settings['category_id'])->toBe($category->id);
});

test('factory creates valid featured products sections', function () {
    $section = StorefrontSection::factory()->featuredProducts()->create();

    expect($section->type)->toBe(StorefrontSectionType::FeaturedProducts)
        ->and($section->settings)->toBeArray()
        ->and($section->settings)->toHaveKey('product_source')
        ->and($section->settings)->toHaveKey('product_limit');
});

test('factory creates valid product carousel sections', function () {
    $section = StorefrontSection::factory()->productCarousel()->create();

    expect($section->type)->toBe(StorefrontSectionType::ProductCarousel)
        ->and($section->settings)->toBeArray()
        ->and($section->settings)->toHaveKey('product_source')
        ->and($section->settings)->toHaveKey('product_limit');
});
