<?php

declare(strict_types=1);

use App\Actions\UpdateStorefrontSectionAction;
use App\DTOs\UpdateStorefrontSectionInput;
use App\Enums\ProductSource;
use App\Enums\StorefrontSectionType;
use App\Models\StorefrontSection;

use function Pest\Laravel\assertDatabaseHas;

covers(UpdateStorefrontSectionAction::class);

uses()->group('actions', 'storefront');

test('updates section with provided data', function () {
    $section = StorefrontSection::factory()->create([
        'title' => 'Old Title',
        'type' => StorefrontSectionType::FeaturedProducts,
        'settings' => ['product_limit' => 4],
        'is_active' => true,
    ]);

    $action = app(UpdateStorefrontSectionAction::class);

    $data = [
        'title' => 'New Title',
        'settings' => ['product_limit' => 8],
        'is_active' => false,
    ];

    $updatedSection = $action->handle($section, UpdateStorefrontSectionInput::fromArray($data));

    expect($updatedSection)->toBe($section)
        ->and($updatedSection->title)->toBe('New Title')
        ->and($updatedSection->settings['product_limit'])->toBe(8)
        ->and($updatedSection->is_active)->toBeFalse();

    assertDatabaseHas('storefront_sections', [
        'id' => $section->id,
        'title' => castAsTranslatableJson('New Title'),
        'is_active' => false,
    ]);
});

test('preserves existing values when not provided', function () {
    $section = StorefrontSection::factory()->create([
        'title' => 'Original Title',
        'type' => StorefrontSectionType::FeaturedProducts,
        'settings' => ['product_limit' => 6],
        'is_active' => true,
    ]);

    $action = app(UpdateStorefrontSectionAction::class);

    $data = [
        'title' => 'Updated Title',
        // Not providing settings or is_active
    ];

    $updatedSection = $action->handle($section, UpdateStorefrontSectionInput::fromArray($data));

    expect($updatedSection->title)->toBe('Updated Title')
        ->and($updatedSection->settings['product_limit'])->toBe(6) // preserved
        ->and($updatedSection->is_active)->toBeTrue(); // preserved
});

test('updates type when provided', function () {
    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::FeaturedProducts,
    ]);

    $action = app(UpdateStorefrontSectionAction::class);

    $data = [
        'type' => StorefrontSectionType::PromoBanners->value,
    ];

    $updatedSection = $action->handle($section, UpdateStorefrontSectionInput::fromArray($data));

    expect($updatedSection->type)->toBe(StorefrontSectionType::PromoBanners);
});

test('updates settings completely when provided', function () {
    $section = StorefrontSection::factory()->create([
        'settings' => [
            'product_source' => ProductSource::Latest->value,
            'product_limit' => 4,
            'column_count' => 2,
        ],
    ]);

    $action = app(UpdateStorefrontSectionAction::class);

    $data = [
        'settings' => [
            'product_source' => ProductSource::Featured->value,
            'product_limit' => 12,
            'column_count' => 4,
            'category_id' => 5,
        ],
    ];

    $updatedSection = $action->handle($section, UpdateStorefrontSectionInput::fromArray($data));

    expect($updatedSection->settings)->toBeArray()
        ->and($updatedSection->settings['product_source'])->toBe(ProductSource::Featured->value)
        ->and($updatedSection->settings['product_limit'])->toBe(12)
        ->and($updatedSection->settings['column_count'])->toBe(4)
        ->and($updatedSection->settings['category_id'])->toBe(5);
});

test('returns the same section instance', function () {
    $section = StorefrontSection::factory()->create();

    $action = app(UpdateStorefrontSectionAction::class);

    $updatedSection = $action->handle($section, UpdateStorefrontSectionInput::fromArray(['title' => 'Updated']));

    expect($updatedSection)->toBe($section)
        ->and($updatedSection->title)->toBe('Updated');
});
