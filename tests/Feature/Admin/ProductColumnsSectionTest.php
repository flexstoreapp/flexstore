<?php

declare(strict_types=1);

use App\Enums\ProductSource;
use App\Enums\StorefrontSectionType;
use App\Models\Category;
use App\Models\StorefrontSection;

uses()->group('storefront');

test('creates product columns section', function () {
    $category = Category::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::ProductColumns->value,
        'title' => 'Collections',
        'settings' => [
            'columns' => [
                ['heading' => 'Top rated', 'product_source' => ProductSource::Latest->value, 'product_limit' => 3],
                ['heading' => 'By category', 'product_source' => ProductSource::Category->value, 'product_limit' => 4, 'category_id' => $category->id],
            ],
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Collections'))->first();

    expect($section->type)->toBe(StorefrontSectionType::ProductColumns)
        ->and($section->settings['columns'])->toHaveCount(2)
        ->and($section->settings['columns'][0]['heading'])->toBe(castAsTranslatableArray('Top rated'))
        ->and($section->settings['columns'][0]['product_source'])->toBe(ProductSource::Latest->value)
        ->and($section->settings['columns'][0]['product_limit'])->toBe(3)
        ->and($section->settings['columns'][1]['heading'])->toBe(castAsTranslatableArray('By category'))
        ->and($section->settings['columns'][1]['category_id'])->toBe($category->id);

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('column heading is required', function () {
    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::ProductColumns->value,
        'title' => 'Collections',
        'settings' => [
            'columns' => [
                ['heading' => '', 'product_source' => ProductSource::Latest->value, 'product_limit' => 3],
            ],
        ],
        'is_active' => true,
    ])->assertInvalid(['settings.columns.0.heading']);
});
