<?php

declare(strict_types=1);

use App\Enums\StorefrontSectionType;
use App\Models\Media;
use App\Models\StorefrontSection;

uses()->group('storefront');

test('creates promo banners section with banners', function () {
    $media = Media::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::PromoBanners->value,
        'title' => 'Promotions',
        'settings' => [
            'banners' => [
                [
                    'image' => $media->id,
                    'title' => 'Kitchen appliances',
                    'subtitle' => 'Up to 30% off this weekend',
                    'url' => '/shop',
                    'text_align' => 'left',
                ],
                [
                    'image' => $media->id,
                    'title' => 'New season sneakers',
                    'subtitle' => 'Fresh drops just landed',
                    'url' => '/shop',
                    'text_align' => 'center',
                ],
            ],
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Promotions'))->first();
    expect($section->settings)->toBeArray()
        ->and($section->settings['banners'])->toHaveCount(2)
        ->and($section->settings['banners'][0]['image'])->toBe($media->id)
        ->and($section->settings['banners'][0]['title'])->toBe(castAsTranslatableArray('Kitchen appliances'))
        ->and($section->settings['banners'][0]['subtitle'])->toBe(castAsTranslatableArray('Up to 30% off this weekend'))
        ->and($section->settings['banners'][0]['url'])->toBe('/shop')
        ->and($section->settings['banners'][0]['text_align'])->toBe('left')
        ->and($section->settings['banners'][1]['text_align'])->toBe('center');

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('accepts corner text alignment positions for banners', function () {
    $media = Media::factory()->create();

    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::PromoBanners->value,
        'title' => 'Promotions',
        'settings' => [
            'banners' => [
                ['image' => $media->id, 'title' => 'Corner', 'text_align' => 'bottom-right'],
            ],
        ],
        'is_active' => true,
    ])->assertSessionHasNoErrors();

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('Promotions'))->first();

    expect($section->settings['banners'][0]['text_align'])->toBe('bottom-right');
});

test('rejects promo banners with invalid text alignment', function () {
    $media = Media::factory()->create();

    actingAsSuperAdmin()
        ->post(route('admin.storefront.homepage.sections.store'), [
            'type' => StorefrontSectionType::PromoBanners->value,
            'title' => 'Promotions',
            'settings' => [
                'banners' => [
                    [
                        'image' => $media->id,
                        'title' => 'Kitchen appliances',
                        'text_align' => 'justify',
                    ],
                ],
            ],
            'is_active' => true,
        ])
        ->assertSessionHasErrors('settings.banners.0.text_align');
});

test('rejects promo banners exceeding the maximum', function () {
    $media = Media::factory()->create();

    $banners = array_fill(0, 5, [
        'image' => $media->id,
        'title' => 'Banner',
        'text_align' => 'left',
    ]);

    actingAsSuperAdmin()
        ->post(route('admin.storefront.homepage.sections.store'), [
            'type' => StorefrontSectionType::PromoBanners->value,
            'title' => 'Promotions',
            'settings' => ['banners' => $banners],
            'is_active' => true,
        ])
        ->assertSessionHasErrors('settings.banners');
});

test('factory creates valid promo banners sections', function () {
    $section = StorefrontSection::factory()->ofType(StorefrontSectionType::PromoBanners)->create();

    expect($section->type)->toBe(StorefrontSectionType::PromoBanners)
        ->and($section->settings)->toBeArray()
        ->and($section->settings)->toHaveKey('banners');
});
