<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Brand;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Translatable\HasTranslations;

covers(Brand::class);

uses()->group('models', 'brand');

test('has factory', function () {
    expect(Brand::factory())->toBeInstanceOf(Factory::class);
});

test('uses HasTranslations trait', function () {
    expect(Brand::class)->toUseTrait(HasTranslations::class);
});

test('has translatable fields', function () {
    $brand = new Brand();

    expect($brand->getTranslatableAttributes())
        ->toContain('name')
        ->toContain('description');
});

test('casts attributes correctly', function () {
    $brand = new Brand();
    $casts = $brand->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('is_active', 'boolean');
});

test('has products relationship', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->create([
        'brand_id' => $brand->id,
    ]);

    expect($brand->products)
        ->toBeInstanceOf(Collection::class)
        ->and($brand->products->first())->toBeInstanceOf(Product::class)
        ->and($brand->products->first()->id)->toBe($product->id);
});

test('has an image relationship', function () {
    $media = Media::factory()->create();
    $brand = Brand::factory()->create(['image_id' => $media->id]);

    expect($brand->image)->toBeInstanceOf(Media::class)
        ->and($brand->image?->id)->toBe($media->id);
});
