<?php

declare(strict_types=1);

use App\Enums\ProductSource;
use App\Enums\StorefrontSectionType;
use App\Models\Category;
use App\Models\StorefrontSection;

uses()->group('storefront');

test('creates product tabs section with tabs', function () {
    $category = Category::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::ProductTabs->value,
        'title' => 'Best sellers',
        'settings' => [
            'tabs' => [
                [
                    'label' => 'Electronics',
                    'product_source' => ProductSource::Latest->value,
                    'product_limit' => 10,
                ],
                [
                    'label' => 'Fashion',
                    'product_source' => ProductSource::Category->value,
                    'product_limit' => 8,
                    'category_id' => $category->id,
                ],
            ],
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Best sellers'))->first();

    expect($section->settings)->toBeArray()
        ->and($section->settings['tabs'])->toHaveCount(2)
        ->and($section->settings['tabs'][0]['label'])->toBe(castAsTranslatableArray('Electronics'))
        ->and($section->settings['tabs'][0]['product_source'])->toBe(ProductSource::Latest->value)
        ->and($section->settings['tabs'][0]['product_limit'])->toBe(10)
        ->and($section->settings['tabs'][1]['label'])->toBe(castAsTranslatableArray('Fashion'))
        ->and($section->settings['tabs'][1]['product_source'])->toBe(ProductSource::Category->value)
        ->and($section->settings['tabs'][1]['category_id'])->toBe($category->id);

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('tab label is required', function () {
    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::ProductTabs->value,
        'title' => 'Best sellers',
        'settings' => [
            'tabs' => [
                [
                    'label' => '',
                    'product_source' => ProductSource::Latest->value,
                    'product_limit' => 10,
                ],
            ],
        ],
        'is_active' => true,
    ])->assertSessionHasErrors('settings.tabs.0.label');
});

test('factory creates valid product tabs sections', function () {
    $section = StorefrontSection::factory()->ofType(StorefrontSectionType::ProductTabs)->create();

    expect($section->type)->toBe(StorefrontSectionType::ProductTabs)
        ->and($section->settings)->toBeArray()
        ->and($section->settings)->toHaveKey('tabs');
});
