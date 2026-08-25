<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\WeightUnit;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Translatable\HasTranslations;

covers(ProductVariant::class);

uses()->group('models', 'product');

test('has factory', function () {
    expect(ProductVariant::factory())->toBeInstanceOf(Factory::class);
});

test('uses HasUuids trait', function () {
    expect(ProductVariant::class)->toUseTrait(HasUuids::class);
});

test('uses HasTranslations trait', function () {
    expect(ProductVariant::class)->toUseTrait(HasTranslations::class);
});

test('has translatable fields', function () {
    $variant = new ProductVariant();

    expect($variant->getTranslatableAttributes())->toContain('title');
});

test('touches product relationship', function () {
    $variant = new ProductVariant();

    expect($variant->getTouchedRelations())->toBe(['product']);
});

test('casts attributes correctly', function () {
    $variant = ProductVariant::factory()->create([
        'price' => '29.9900',
        'compare_at_price' => '39.9900',
        'cost_per_item' => '15.5000',
        'weight' => '2.50',
        'weight_unit' => WeightUnit::Kg,
    ]);

    $casts = $variant->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('price', 'decimal:4')
        ->toHaveKey('compare_at_price', 'decimal:4')
        ->toHaveKey('cost_per_item', 'decimal:4')
        ->toHaveKey('weight', 'decimal:2')
        ->toHaveKey('track_stock', 'boolean')
        ->toHaveKey('in_stock', 'boolean')
        ->toHaveKey('is_default', 'boolean')
        ->toHaveKey('weight_unit', WeightUnit::class);
});

test('has product relationship', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);

    expect($variant->product)->toBeInstanceOf(Product::class)
        ->and($variant->product->id)->toBe($product->id);
});

test('has options relationship', function () {
    $variant = ProductVariant::factory()->create();
    $option = ProductVariantOption::factory()->create([
        'product_variant_id' => $variant->id,
    ]);

    expect($variant->options)->toBeInstanceOf(Collection::class)
        ->and($variant->options->first()->id)->toBe($option->id);
});

test('media relation returns the linked media', function () {
    $media = Media::factory()->uploaded()->create();
    $variant = ProductVariant::factory()->create(['media_id' => $media->id]);

    expect($variant->media)->toBeInstanceOf(Media::class)
        ->and($variant->media->id)->toBe($media->id)
        ->and($variant->media->thumbnail_url)->toBe($media->thumbnail_url);
});

test('media relation is null when variant has no media', function () {
    $variant = ProductVariant::factory()->create(['media_id' => null]);

    expect($variant->media)->toBeNull();
});

test('media serializes into the array when loaded', function () {
    $media = Media::factory()->uploaded()->create();
    $variant = ProductVariant::factory()->create(['media_id' => $media->id]);

    $variant->load('media');

    expect($variant->toArray())->toHaveKey('media');
});

test('casts decimal values correctly', function () {
    $variant = ProductVariant::factory()->create([
        'price' => '29.9900',
        'compare_at_price' => '39.9900',
        'cost_per_item' => '15.5000',
        'weight' => '2.50',
    ]);

    expect($variant->price)->toBe('29.9900');
    expect($variant->compare_at_price)->toBe('39.9900');
    expect($variant->cost_per_item)->toBe('15.5000');
    expect($variant->weight)->toBe('2.50');
});

test('handles nullable decimal values', function () {
    $variant = ProductVariant::factory()->create([
        'compare_at_price' => null,
        'cost_per_item' => null,
        'weight' => null,
    ]);

    expect($variant->compare_at_price)->toBeNull();
    expect($variant->cost_per_item)->toBeNull();
    expect($variant->weight)->toBeNull();
});

test('handles decimal precision for monetary values', function () {
    $variant = ProductVariant::factory()->create([
        'price' => '19.999',
        'compare_at_price' => '29.001',
        'cost_per_item' => '9.555',
    ]);

    expect($variant->price)->toBe('19.9990');
    expect($variant->compare_at_price)->toBe('29.0010');
    expect($variant->cost_per_item)->toBe('9.5550');
});

test('handles weight with decimal precision', function () {
    $variant = ProductVariant::factory()->create([
        'weight' => '2.567',
    ]);

    expect($variant->weight)->toBe('2.57');
});

test('casts weight unit enum correctly', function () {
    $variant = ProductVariant::factory()->create(['weight_unit' => WeightUnit::Kg]);

    expect($variant->weight_unit)->toBeInstanceOf(WeightUnit::class);
    expect($variant->weight_unit)->toBe(WeightUnit::Kg);
});

test('handles all weight unit enum values', function () {
    $weightUnits = [WeightUnit::Kg, WeightUnit::G, WeightUnit::Lb, WeightUnit::Oz];

    foreach ($weightUnits as $unit) {
        $variant = ProductVariant::factory()->create(['weight_unit' => $unit]);

        expect($variant->weight_unit)->toBe($unit);
    }
});

test('casts boolean values correctly', function () {
    $variant = ProductVariant::factory()->create([
        'track_stock' => true,
        'in_stock' => true,
        'is_default' => false,
    ]);

    expect($variant->track_stock)->toBeBool()->toBeTrue();
    expect($variant->in_stock)->toBeBool()->toBeTrue();
    expect($variant->is_default)->toBeBool()->toBeFalse();
});

test('generates uuid for id', function () {
    $variant = ProductVariant::factory()->create();

    expect($variant->id)->toBeString();
    expect(mb_strlen($variant->id))->toBe(36); // UUID length
    expect($variant->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

test('has unique ids across multiple variants', function () {
    $variant1 = ProductVariant::factory()->create();
    $variant2 = ProductVariant::factory()->create();

    expect($variant1->id)->not->toBe($variant2->id);
});

test('handles zero quantity', function () {
    $variant = ProductVariant::factory()->create([
        'stock' => 0,
    ]);

    expect($variant->stock)->toBe(0);
});

test('handles negative quantity', function () {
    $variant = ProductVariant::factory()->create([
        'stock' => -5,
    ]);

    expect($variant->stock)->toBe(-5);
});

test('handles empty options collection', function () {
    $variant = ProductVariant::factory()->create();

    expect($variant->options)->toBeInstanceOf(Collection::class);
    expect($variant->options)->toHaveCount(0);
});

test('has stock movements relationship', function () {
    $variant = ProductVariant::factory()->create();
    $stockMovement = StockMovement::factory()->create([
        'product_variant_id' => $variant->id,
    ]);

    expect($variant->stockMovements)->toBeInstanceOf(Collection::class)
        ->and($variant->stockMovements->first())->toBeInstanceOf(StockMovement::class)
        ->and($variant->stockMovements->first()->id)->toBe($stockMovement->id);
});

test('handles empty stock movements collection', function () {
    $variant = ProductVariant::factory()->create();

    expect($variant->stockMovements)->toBeInstanceOf(Collection::class);
    expect($variant->stockMovements)->toHaveCount(0);
});

test('isLowStock accessor returns false when track_stock is disabled', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => false,
        'stock' => 5,
        'low_stock_threshold' => 10,
    ]);

    expect($variant->is_low_stock)->toBeFalse();
});

test('isLowStock accessor returns false when stock is null', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => null,
        'low_stock_threshold' => 10,
    ]);

    expect($variant->is_low_stock)->toBeFalse();
});

test('isLowStock accessor returns false when stock is above threshold', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => 15,
        'low_stock_threshold' => 10,
    ]);

    expect($variant->is_low_stock)->toBeFalse();
});

test('isLowStock accessor returns true when stock equals threshold', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => 10,
        'low_stock_threshold' => 10,
    ]);

    expect($variant->is_low_stock)->toBeTrue();
});

test('isLowStock accessor returns true when stock is below threshold', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => 5,
        'low_stock_threshold' => 10,
    ]);

    expect($variant->is_low_stock)->toBeTrue();
});

test('isLowStock accessor uses variant threshold when set', function () {
    $product = Product::factory()->create(['low_stock_threshold' => 20]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => 7,
        'low_stock_threshold' => 8, // Variant threshold
    ]);

    expect($variant->is_low_stock)->toBeTrue();
});

test('isLowStock accessor falls back to product threshold when variant threshold not set', function () {
    $product = Product::factory()->create(['low_stock_threshold' => 8]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => 5,
        'low_stock_threshold' => null, // No variant threshold
    ]);

    expect($variant->is_low_stock)->toBeTrue();
});
