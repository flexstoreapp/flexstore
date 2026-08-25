<?php

declare(strict_types=1);

use App\Enums\ProductSource;
use App\Enums\StorefrontSectionType;
use App\Http\Controllers\Admin\StorefrontSectionController;
use App\Http\Requests\Admin\StoreStorefrontSectionRequest;
use App\Models\Category;
use App\Models\StorefrontSection;

uses()->group('storefront');

covers(StorefrontSectionController::class, StoreStorefrontSectionRequest::class);

test('creates featured products section', function () {
    $category = Category::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::FeaturedProducts->value,
        'title' => 'Featured products',
        'settings' => [
            'product_source' => ProductSource::Category->value,
            'product_limit' => 8,
            'category_id' => $category->id,
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()
        ->where('title', castAsTranslatableJson('Featured products'))
        ->first();

    expect($section->settings)->toBeArray()
        ->and($section->settings['product_source'])->toBe(ProductSource::Category->value)
        ->and($section->settings['product_limit'])->toBe(8)
        ->and($section->settings['category_id'])->toBe($category->id);

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('featured products rejects an invalid product source', function () {
    actingAsSuperAdmin()
        ->post(route('admin.storefront.homepage.sections.store'), [
            'type' => StorefrontSectionType::FeaturedProducts->value,
            'title' => 'Featured products',
            'settings' => [
                'product_source' => 'not-a-source',
                'product_limit' => 8,
            ],
            'is_active' => true,
        ])
        ->assertInvalid(['settings.product_source']);
});
