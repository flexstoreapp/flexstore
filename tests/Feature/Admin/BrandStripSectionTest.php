<?php

declare(strict_types=1);

use App\Enums\StorefrontSectionType;
use App\Models\Brand;
use App\Models\StorefrontSection;

uses()->group('storefront');

test('creates brand strip section', function () {
    $brands = Brand::factory()->count(3)->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::BrandStrip->value,
        'title' => 'Our Brands',
        'settings' => [
            'brand_ids' => $brands->pluck('id')->all(),
            'grayscale' => true,
            'view_all_url' => '/brands',
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Our Brands'))->first();
    expect($section->settings)->toBeArray()
        ->and($section->settings['brand_ids'])->toBe($brands->pluck('id')->all())
        ->and($section->settings['grayscale'])->toBeTrue()
        ->and($section->settings['view_all_url'])->toBe('/brands');

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('brand ids must reference existing brands', function () {
    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::BrandStrip->value,
        'title' => 'Our Brands',
        'settings' => [
            'brand_ids' => [999999],
            'grayscale' => false,
        ],
        'is_active' => true,
    ])->assertInvalid(['settings.brand_ids.0']);
});
