<?php

declare(strict_types=1);

use App\Enums\StorefrontSectionType;
use App\Http\Controllers\Api\V1\HomepageController;
use App\Http\Resources\Api\V1\HomepageSectionResource;
use App\Models\Media;
use App\Models\Product;
use App\Models\StorefrontSection;

use function Pest\Laravel\getJson;

covers(HomepageController::class, HomepageSectionResource::class);

uses()->group('api');

test('sections are returned in their configured order', function (): void {
    StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::InfoStrip,
        'is_active' => true,
        'sort_order' => 2,
        'settings' => ['items' => []],
    ]);
    $first = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::Testimonials,
        'is_active' => true,
        'sort_order' => 1,
        'settings' => ['testimonials' => []],
    ]);

    getJson(route('api.v1.homepage'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $first->id)
        ->assertJsonPath('data.0.type', StorefrontSectionType::Testimonials->value);
});

test('an inactive section is not returned', function (): void {
    StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::InfoStrip,
        'is_active' => false,
        'settings' => ['items' => []],
    ]);

    getJson(route('api.v1.homepage'))->assertOk()->assertJsonCount(0, 'data');
});

test('titles and nested labels come back as plain strings', function (): void {
    StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::InfoStrip,
        'is_active' => true,
        'title' => ['en' => 'Why shop with us', 'ar' => 'لماذا تتسوق معنا'],
        'settings' => [
            'items' => [
                ['icon_name' => 'shipping', 'title' => ['en' => 'Free shipping'], 'subtitle' => ['en' => 'Over $50']],
            ],
        ],
    ]);

    getJson(route('api.v1.homepage'))
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Why shop with us')
        ->assertJsonPath('data.0.settings.items.0.title', 'Free shipping')
        ->assertJsonPath('data.0.settings.items.0.subtitle', 'Over $50')
        ->assertJsonPath('data.0.settings.items.0.icon_name', 'shipping');
});

test('links carry a resolved target a native client can navigate', function (): void {
    StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::PromoBanners,
        'is_active' => true,
        'settings' => [
            'view_all_url' => '/shop?on_sale=true',
            'banners' => [
                ['title' => ['en' => 'Winter'], 'url' => '/categories/knitwear', 'image' => null],
                ['title' => ['en' => 'Partner'], 'url' => 'https://example.com/promo', 'image' => null],
            ],
        ],
    ]);

    getJson(route('api.v1.homepage'))
        ->assertOk()
        ->assertJsonPath('data.0.settings.view_all_url', '/shop?on_sale=true')
        ->assertJsonPath('data.0.settings.view_all_target', ['type' => 'shop', 'query' => ['on_sale' => 'true']])
        ->assertJsonPath('data.0.settings.banners.0.target', ['type' => 'category', 'handle' => 'knitwear'])
        ->assertJsonPath('data.0.settings.banners.1.target', ['type' => 'external', 'url' => 'https://example.com/promo']);
});

test('section images are returned as media objects, not raw ids', function (): void {
    $media = Media::factory()->create();

    StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::HeroSlider,
        'is_active' => true,
        'settings' => [
            'slides' => [
                ['image' => $media->id, 'headline' => ['en' => 'Winter sale'], 'button_url' => '/shop'],
            ],
        ],
    ]);

    getJson(route('api.v1.homepage'))
        ->assertOk()
        ->assertJsonPath('data.0.settings.slides.0.image.id', $media->id)
        ->assertJsonPath('data.0.settings.slides.0.image.url', $media->url)
        ->assertJsonPath('data.0.settings.slides.0.headline', 'Winter sale')
        ->assertJsonPath('data.0.settings.slides.0.button_target', ['type' => 'shop']);
});

test('product sections resolve their products', function (): void {
    $product = Product::factory()->available()->create(['title' => 'Merino crew knit']);

    StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::FeaturedProducts,
        'is_active' => true,
        'settings' => ['product_source' => 'featured', 'product_ids' => [$product->id], 'product_limit' => 4],
    ]);

    getJson(route('api.v1.homepage'))
        ->assertOk()
        ->assertJsonPath('data.0.settings.products.0.id', $product->id)
        ->assertJsonPath('data.0.settings.products.0.title', 'Merino crew knit');
});
