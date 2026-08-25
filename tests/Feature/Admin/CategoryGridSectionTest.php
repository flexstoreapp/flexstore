<?php

declare(strict_types=1);

use App\Enums\StorefrontSectionType;
use App\Http\Controllers\Admin\StorefrontSectionController;
use App\Http\Requests\Admin\StoreStorefrontSectionRequest;
use App\Models\Category;
use App\Models\Media;
use App\Models\StorefrontSection;

uses()->group('storefront');

covers(StorefrontSectionController::class, StoreStorefrontSectionRequest::class);

test('creates category grid section', function () {
    $category = Category::factory()->create();
    $media = Media::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::CategoryGrid->value,
        'title' => 'Shop by category',
        'settings' => [
            'categories' => [
                ['category_id' => $category->id, 'image' => $media->id, 'text_color' => 'light'],
            ],
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Shop by category'))->first();

    expect($section->type)->toBe(StorefrontSectionType::CategoryGrid)
        ->and($section->settings['categories'][0]['category_id'])->toBe($category->id)
        ->and($section->settings['categories'][0]['image'])->toBe($media->id)
        ->and($section->settings['categories'][0]['text_color'])->toBe('light');

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('category grid rejects invalid text color', function () {
    $category = Category::factory()->create();

    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::CategoryGrid->value,
        'title' => 'Shop by category',
        'settings' => [
            'categories' => [
                ['category_id' => $category->id, 'text_color' => 'neon'],
            ],
        ],
        'is_active' => true,
    ])->assertSessionHasErrors('settings.categories.0.text_color');
});

test('category grid requires a category id for each item', function () {
    $media = Media::factory()->create();

    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::CategoryGrid->value,
        'title' => 'Shop by category',
        'settings' => [
            'categories' => [
                ['image' => $media->id],
            ],
        ],
        'is_active' => true,
    ])->assertSessionHasErrors('settings.categories.0.category_id');
});
