<?php

declare(strict_types=1);

use App\Enums\StorefrontSectionType;
use App\Models\StorefrontSection;

uses()->group('storefront');

test('creates testimonials section with items', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::Testimonials->value,
        'title' => 'What our customers say',
        'settings' => [
            'testimonials' => [
                [
                    'quote' => 'Easily the smoothest online shopping I have had this year.',
                    'author_name' => 'Sarah M.',
                    'rating' => 5,
                ],
                [
                    'quote' => 'The return process was painless.',
                    'author_name' => 'James K.',
                    'rating' => 4,
                ],
            ],
        ],
        'is_active' => true,
    ]);

    $section = StorefrontSection::query()->where('title', castAsTranslatableJson('What our customers say'))->first();

    expect($section->settings)->toBeArray()
        ->and($section->settings['testimonials'])->toHaveCount(2)
        ->and($section->settings['testimonials'][0]['quote'])->toBe(castAsTranslatableArray('Easily the smoothest online shopping I have had this year.'))
        ->and($section->settings['testimonials'][0]['author_name'])->toBe(castAsTranslatableArray('Sarah M.'))
        ->and($section->settings['testimonials'][0]['rating'])->toBe(5);

    $response->assertRedirect(route('admin.storefront.homepage.sections.edit', $section))
        ->assertSessionHasNoErrors();
});

test('testimonial quote and author name are required', function () {
    actingAsSuperAdmin()->post(route('admin.storefront.homepage.sections.store'), [
        'type' => StorefrontSectionType::Testimonials->value,
        'title' => 'What our customers say',
        'settings' => [
            'testimonials' => [
                [
                    'quote' => '',
                    'author_name' => '',
                    'rating' => 5,
                ],
            ],
        ],
        'is_active' => true,
    ])->assertInvalid(['settings.testimonials.0.quote', 'settings.testimonials.0.author_name']);
});
