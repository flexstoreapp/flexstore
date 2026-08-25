<?php

declare(strict_types=1);

use App\Actions\StoreStorefrontSectionAction;
use App\Enums\StorefrontSectionType;
use App\Http\Controllers\Admin\StorefrontSectionController;
use App\Http\Requests\Admin\StoreStorefrontSectionRequest;
use App\Models\Brand;
use App\Models\StorefrontSection;

covers(StorefrontSectionController::class, StoreStorefrontSectionRequest::class, StoreStorefrontSectionAction::class);

uses()->group('storefront');

test('displays create section page', function () {
    $response = actingAsSuperAdmin()->get(route('admin.storefront.homepage.sections.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/storefront/sections/create'));
});

test('creates a section with products sourced from a brand', function () {
    $brand = Brand::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::FeaturedProducts->value,
        'title' => 'Brand Products',
        'is_active' => true,
        'settings' => [
            'product_source' => 'brand',
            'brand_id' => $brand->id,
            'product_limit' => 8,
        ],
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Brand Products'))->firstOrFail();

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();

    expect($section->settings['product_source'])->toBe('brand')
        ->and($section->settings['brand_id'])->toBe($brand->id);
});

test('validates required type field', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'title' => 'Test Section',
        'is_active' => true,
    ]);

    $response->assertRedirect()
        ->assertInvalid('type');
});

test('validates section type enum', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => 'invalid_type',
        'title' => 'Test Section',
        'is_active' => true,
    ]);

    $response->assertRedirect()
        ->assertInvalid('type');
});

test('validates brand source selection', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::FeaturedProducts->value,
        'title' => 'Brand Products',
        'settings' => [
            'product_source' => 'brand',
            'brand_id' => 999999,
        ],
    ]);

    $response->assertRedirect()
        ->assertInvalid('settings.brand_id');
});
