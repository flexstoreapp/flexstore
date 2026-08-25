<?php

declare(strict_types=1);

use App\Enums\StorefrontSectionType;
use App\Models\StorefrontSection;

uses()->group('storefront');

test('creates info strip section with items', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::InfoStrip->value,
        'title' => 'Why shop with us',
        'settings' => [
            'items' => [
                ['icon_name' => 'shipping', 'title' => 'Free shipping', 'subtitle' => 'On orders over $75'],
                ['icon_name' => 'returns', 'title' => '30-day returns', 'subtitle' => 'Hassle-free guarantee'],
            ],
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Why shop with us'))->first();

    expect($section->settings)->toBeArray()
        ->and($section->settings['items'][0]['icon_name'])->toBe('shipping')
        ->and($section->settings['items'][0]['title'])->toBe(castAsTranslatableArray('Free shipping'))
        ->and($section->settings['items'][0]['subtitle'])->toBe(castAsTranslatableArray('On orders over $75'))
        ->and($section->settings['items'][1]['icon_name'])->toBe('returns');

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('info strip item requires an icon name', function () {
    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::InfoStrip->value,
        'title' => 'Why shop with us',
        'settings' => [
            'items' => [
                ['icon_name' => '', 'title' => 'Free shipping'],
            ],
        ],
        'is_active' => true,
    ])->assertInvalid(['settings.items.0.icon_name']);
});

test('factory creates valid info strip sections', function () {
    $section = StorefrontSection::factory()->ofType(StorefrontSectionType::InfoStrip)->create();

    expect($section->type)->toBe(StorefrontSectionType::InfoStrip)
        ->and($section->settings)->toBeArray()
        ->and($section->settings)->toHaveKey('items');
});
