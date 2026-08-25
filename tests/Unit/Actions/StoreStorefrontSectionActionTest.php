<?php

declare(strict_types=1);

use App\Actions\StoreStorefrontSectionAction;
use App\DTOs\StoreStorefrontSectionInput;
use App\Enums\ProductSource;
use App\Enums\StorefrontSectionType;
use App\Models\StorefrontSection;

use function Pest\Laravel\assertDatabaseHas;

covers(StoreStorefrontSectionAction::class);

uses()->group('actions', 'storefront');

test('creates a storefront section with provided data', function () {
    $action = app(StoreStorefrontSectionAction::class);

    $data = [
        'type' => StorefrontSectionType::FeaturedProducts->value,
        'title' => 'Featured Products',
        'settings' => [
            'product_source' => ProductSource::Latest->value,
            'product_limit' => 8,
            'column_count' => 4,
        ],
        'is_active' => true,
    ];

    $section = $action->handle(StoreStorefrontSectionInput::fromArray($data));

    expect($section)->toBeInstanceOf(StorefrontSection::class)
        ->and($section->type)->toBe(StorefrontSectionType::FeaturedProducts)
        ->and($section->title)->toBe('Featured Products')
        ->and($section->settings)->toBeArray()
        ->and($section->settings['product_source'])->toBe(ProductSource::Latest->value)
        ->and($section->settings['product_limit'])->toBe(8)
        ->and($section->settings['column_count'])->toBe(4)
        ->and($section->is_active)->toBeTrue()
        ->and($section->sort_order)->toBe(0);

    assertDatabaseHas('storefront_sections', [
        'type' => StorefrontSectionType::FeaturedProducts->value,
        'title' => castAsTranslatableJson('Featured Products'),
        'is_active' => true,
        'sort_order' => 0,
    ]);
});

test('auto-increments sort_order when sections exist', function () {
    StorefrontSection::factory()->create(['sort_order' => 5]);
    StorefrontSection::factory()->create(['sort_order' => 3]);

    $action = app(StoreStorefrontSectionAction::class);

    $data = [
        'type' => StorefrontSectionType::PromoBanners->value,
        'title' => 'New Banner',
        'is_active' => true,
    ];

    $section = $action->handle(StoreStorefrontSectionInput::fromArray($data));

    expect($section->sort_order)->toBe(6); // max sort_order + 1
});

test('sets default sort_order to 0 when no sections exist', function () {
    $action = app(StoreStorefrontSectionAction::class);

    $data = [
        'type' => StorefrontSectionType::ProductCarousel->value,
        'title' => 'First Section',
    ];

    $section = $action->handle(StoreStorefrontSectionInput::fromArray($data));

    expect($section->sort_order)->toBe(0);
});

test('sets default settings to empty array when not provided', function () {
    $action = app(StoreStorefrontSectionAction::class);

    $data = [
        'type' => StorefrontSectionType::PromoBanners->value,
        'title' => 'Simple Banner',
    ];

    $section = $action->handle(StoreStorefrontSectionInput::fromArray($data));

    expect($section->settings)->toBeArray()
        ->and($section->settings)->toBeEmpty();
});

test('sets default is_active to true when not provided', function () {
    $action = app(StoreStorefrontSectionAction::class);

    $data = [
        'type' => StorefrontSectionType::FeaturedProducts->value,
        'title' => 'Active Section',
    ];

    $section = $action->handle(StoreStorefrontSectionInput::fromArray($data));

    expect($section->is_active)->toBeTrue();
});

test('respects is_active value when provided', function () {
    $action = app(StoreStorefrontSectionAction::class);

    $data = [
        'type' => StorefrontSectionType::FeaturedProducts->value,
        'title' => 'Inactive Section',
        'is_active' => false,
    ];

    $section = $action->handle(StoreStorefrontSectionInput::fromArray($data));

    expect($section->is_active)->toBeFalse();
});
