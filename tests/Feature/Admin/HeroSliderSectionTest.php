<?php

declare(strict_types=1);

use App\Enums\StorefrontSectionType;
use App\Http\Controllers\Admin\StorefrontSectionController;
use App\Http\Requests\Admin\StoreStorefrontSectionRequest;
use App\Models\Media;
use App\Models\StorefrontSection;

uses()->group('storefront');

covers(StorefrontSectionController::class, StoreStorefrontSectionRequest::class);

test('creates hero slider section', function () {
    $slideImage = Media::factory()->create();
    $tileImage = Media::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::HeroSlider->value,
        'title' => 'Welcome',
        'settings' => [
            'slides' => [
                [
                    'image' => $slideImage->id,
                    'headline' => 'Summer sale up to 50% off',
                    'subtext' => 'Thousands of deals across every department.',
                    'button_text' => 'Shop now',
                    'button_url' => '/products',
                ],
            ],
            'side_tiles' => [
                [
                    'image' => $tileImage->id,
                    'title' => 'Headphones',
                    'subtitle' => 'From $49',
                    'url' => '/products',
                ],
            ],
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Welcome'))->first();

    expect($section->settings)->toBeArray()
        ->and($section->settings['slides'][0]['image'])->toBe($slideImage->id)
        ->and($section->settings['slides'][0]['headline'])->toBe(castAsTranslatableArray('Summer sale up to 50% off'))
        ->and($section->settings['slides'][0]['subtext'])->toBe(castAsTranslatableArray('Thousands of deals across every department.'))
        ->and($section->settings['slides'][0]['button_text'])->toBe(castAsTranslatableArray('Shop now'))
        ->and($section->settings['slides'][0]['button_url'])->toBe('/products')
        ->and($section->settings['side_tiles'][0]['image'])->toBe($tileImage->id)
        ->and($section->settings['side_tiles'][0]['title'])->toBe(castAsTranslatableArray('Headphones'))
        ->and($section->settings['side_tiles'][0]['subtitle'])->toBe(castAsTranslatableArray('From $49'))
        ->and($section->settings['side_tiles'][0]['url'])->toBe('/products');

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('stores per-item text alignment for slides and side tiles', function () {
    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::HeroSlider->value,
        'title' => 'Welcome',
        'settings' => [
            'slides' => [['headline' => 'Hello', 'text_align' => 'bottom-right']],
            'side_tiles' => [['title' => 'Tile', 'text_align' => 'top-center']],
        ],
        'is_active' => true,
    ])->assertSessionHasNoErrors();

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Welcome'))->first();

    expect($section->settings['slides'][0]['text_align'])->toBe('bottom-right')
        ->and($section->settings['side_tiles'][0]['text_align'])->toBe('top-center');
});

test('rejects an invalid slide text alignment', function () {
    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::HeroSlider->value,
        'title' => 'Welcome',
        'settings' => [
            'slides' => [['headline' => 'Hello', 'text_align' => 'middle']],
        ],
        'is_active' => true,
    ])->assertSessionHasErrors('settings.slides.0.text_align');
});

test('rejects an invalid side tile text alignment', function () {
    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::HeroSlider->value,
        'title' => 'Welcome',
        'settings' => [
            'side_tiles' => [['title' => 'Tile', 'text_align' => 'diagonal']],
        ],
        'is_active' => true,
    ])->assertSessionHasErrors('settings.side_tiles.0.text_align');
});

test('rejects more than five slides', function () {
    $slides = collect(range(1, 6))->map(fn (int $i): array => [
        'headline' => "Slide {$i}",
    ])->all();

    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::HeroSlider->value,
        'title' => 'Welcome',
        'settings' => [
            'slides' => $slides,
            'side_tiles' => [],
        ],
        'is_active' => true,
    ])->assertInvalid('settings.slides');
});

test('factory creates valid hero slider sections', function () {
    $section = StorefrontSection::factory()->ofType(StorefrontSectionType::HeroSlider)->create();

    expect($section->type)->toBe(StorefrontSectionType::HeroSlider)
        ->and($section->settings)->toBeArray()
        ->and($section->settings)->toHaveKey('slides')
        ->and($section->settings)->toHaveKey('side_tiles');
});
