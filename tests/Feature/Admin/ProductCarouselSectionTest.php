<?php

declare(strict_types=1);

use App\Enums\ProductSource;
use App\Enums\StorefrontSectionType;
use App\Http\Requests\Admin\StoreStorefrontSectionRequest;
use App\Http\Requests\Admin\UpdateStorefrontSectionRequest;
use App\Models\StorefrontSection;

uses()->group('storefront');

covers(StoreStorefrontSectionRequest::class, UpdateStorefrontSectionRequest::class);

test('creates product carousel section', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::ProductCarousel->value,
        'title' => 'New arrivals',
        'settings' => [
            'product_source' => ProductSource::Latest->value,
            'product_limit' => 12,
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('New arrivals'))->first();
    expect($section->settings)->toBeArray()
        ->and($section->settings['product_source'])->toBe(ProductSource::Latest->value)
        ->and($section->settings['product_limit'])->toBe(12);

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('product carousel rejects an invalid product source', function () {
    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::ProductCarousel->value,
        'title' => 'New arrivals',
        'settings' => [
            'product_source' => 'not-a-source',
            'product_limit' => 12,
        ],
        'is_active' => true,
    ])->assertInvalid(['settings.product_source']);
});

test('updates a product carousel section', function () {
    $section = StorefrontSection::factory()->ofType(StorefrontSectionType::ProductCarousel)->create();

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.homepage.sections.edit', $section))
        ->patch(route('admin.storefront.homepage.sections.update', $section), [
            'type' => StorefrontSectionType::ProductCarousel->value,
            'title' => 'Fresh picks',
            'settings' => [
                'product_source' => ProductSource::Featured->value,
                'product_limit' => 8,
            ],
        ]);

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();

    $section->refresh();
    expect($section->settings['product_source'])->toBe(ProductSource::Featured->value)
        ->and($section->settings['product_limit'])->toBe(8);
});
