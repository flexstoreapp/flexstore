<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Actions\SyncMediaAction;
use App\Enums\TaxCategory;
use App\Enums\WeightUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(Product::class);

uses()->group('models', 'product');

test('has factory', function () {
    expect(Product::factory())->toBeInstanceOf(Factory::class);
});

test('casts weight unit enum correctly', function () {
    $product = Product::factory()->create(['weight_unit' => WeightUnit::Kg]);

    expect($product->weight_unit)->toBeInstanceOf(WeightUnit::class);
    expect($product->weight_unit)->toBe(WeightUnit::Kg);
});

test('has category relationship', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
    ]);

    expect($product->category)->toBeInstanceOf(Category::class)
        ->and($product->category->id)->toBe($category->id);
});

test('has brand relationship', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->create([
        'brand_id' => $brand->id,
    ]);

    expect($product->brand)->toBeInstanceOf(Brand::class)
        ->and($product->brand->id)->toBe($brand->id);
});

test('handles null category relationship', function () {
    $product = Product::factory()->create(['category_id' => null]);

    expect($product->category)->toBeNull();
});

test('has options relationship', function () {
    $product = Product::factory()->create();
    $option = ProductOption::factory()->create([
        'product_id' => $product->id,
    ]);

    expect($product->options)->toBeInstanceOf(Collection::class)
        ->and($product->options->first())->toBeInstanceOf(ProductOption::class)
        ->and($product->options->first()->id)->toBe($option->id);
});

test('handles empty options collection', function () {
    $product = Product::factory()->create();

    expect($product->options)->toBeInstanceOf(Collection::class);
    expect($product->options)->toHaveCount(0);
});

test('has variants relationship', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);

    expect($product->variants)->toBeInstanceOf(Collection::class)
        ->and($product->variants->first())->toBeInstanceOf(ProductVariant::class)
        ->and($product->variants->first()->id)->toBe($variant->id);
});

test('handles empty variants collection', function () {
    $product = Product::factory()->create();

    expect($product->variants)->toBeInstanceOf(Collection::class);
    expect($product->variants)->toHaveCount(0);
});

test('casts tax category to enum', function () {
    $product = Product::factory()->create([
        'tax_category' => TaxCategory::Electronics,
    ]);

    expect($product->tax_category)->toBe(TaxCategory::Electronics);
});

test('handles null tax category', function () {
    $product = Product::factory()->create([
        'is_tax_exempt' => true,
        'tax_category' => null,
    ]);

    expect($product->tax_category)->toBeNull();
});

test('has stock movements relationship', function () {
    $product = Product::factory()->create();
    $stockMovement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'product_variant_id' => null,
    ]);

    expect($product->stockMovements)->toBeInstanceOf(Collection::class)
        ->and($product->stockMovements->first())->toBeInstanceOf(StockMovement::class)
        ->and($product->stockMovements->first()->id)->toBe($stockMovement->id);
});

test('stock movements relationship excludes variant movements', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $productMovement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'product_variant_id' => null,
    ]);

    StockMovement::factory()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
    ]);

    expect($product->stockMovements)->toHaveCount(1)
        ->and($product->stockMovements->first()->id)->toBe($productMovement->id);
});

test('handles empty stock movements collection', function () {
    $product = Product::factory()->create();

    expect($product->stockMovements)->toBeInstanceOf(Collection::class);
    expect($product->stockMovements)->toHaveCount(0);
});

test('has reviews relationship', function () {
    $product = Product::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
    ]);

    expect($product->reviews)->toBeInstanceOf(Collection::class)
        ->and($product->reviews->first())->toBeInstanceOf(Review::class)
        ->and($product->reviews->first()->id)->toBe($review->id);
});

test('handles empty reviews collection', function () {
    $product = Product::factory()->create();

    expect($product->reviews)->toBeInstanceOf(Collection::class);
    expect($product->reviews)->toHaveCount(0);
});

test('media relation returns attached media in order', function () {
    $mediaA = Media::factory()->create();
    $mediaB = Media::factory()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$mediaA->id, $mediaB->id]);

    $product->load('mediaGallery');

    expect($product->mediaGallery)
        ->toHaveCount(2)
        ->and($product->mediaGallery->pluck('id')->all())->toBe([$mediaA->id, $mediaB->id]);
});

test('featured_media returns null when product has no media', function () {
    $product = Product::factory()->create();
    $product->load('mediaGallery');

    expect($product->featured_media)->toBeNull();
});

test('featured_media returns the first product media', function () {
    $media = Media::factory()->uploaded()->create();
    $second = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$media->id, $second->id]);

    $product->load('mediaGallery');

    expect($product->featured_media)->toBeInstanceOf(Media::class)
        ->and($product->featured_media->id)->toBe($media->id)
        ->and($product->featured_media->thumbnail_url)->toBe($media->thumbnail_url);
});

test('featured_media ignores the default variant media', function () {
    $variantMedia = Media::factory()->uploaded()->create();
    $productMedia = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$productMedia->id]);
    $variant = ProductVariant::factory()->for($product)->create(['is_default' => true]);
    $variant->update(['media_id' => $variantMedia->id]);

    $product->load('mediaGallery');

    expect($product->featured_media->id)->toBe($productMedia->id);
});

test('featured_media lazily loads media when relation is not loaded', function () {
    $media = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$media->id]);

    $fresh = Product::query()->find($product->id);

    expect($fresh->relationLoaded('mediaGallery'))->toBeFalse()
        ->and($fresh->featured_media)->toBeInstanceOf(Media::class)
        ->and($fresh->featured_media->id)->toBe($media->id);
});

test('withFeaturedMedia eager loads the first gallery item for integer product ids', function () {
    $media = Media::factory()->uploaded()->create();
    $extra = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$media->id, $extra->id]);

    $loaded = Product::query()->withFeaturedMedia()->findOrFail($product->id);

    expect($loaded->relationLoaded('mediaGallery'))->toBeTrue()
        ->and($loaded->mediaGallery)->toHaveCount(1)
        ->and($loaded->featured_media?->id)->toBe($media->id);
});

test('totalStock accessor returns sum of variants quantities', function () {
    $product = new Product();

    // Mock the variants relationship to return a collection with sum method
    $variantsCollection = collect([
        (object) ['stock' => 5],
        (object) ['stock' => 8],
    ]);

    $product->setRelation('variants', $variantsCollection);

    expect($product->totalStock)
        ->toBeInt()
        ->toBe(13); // 5 + 8
});

test('totalStock accessor returns product quantity when no variants', function () {
    $product = new Product();
    $product->setAttribute('stock', 15);

    $product->setRelation('variants', collect([]));

    expect($product->totalStock)
        ->toBeInt()
        ->toBe(15);
});

test('totalStock handles zero quantities correctly', function () {
    $product = new Product();
    $product->setAttribute('stock', 0);
    $variantsCollection = collect([
        (object) ['stock' => 0],
        (object) ['stock' => 0],
    ]);
    $product->setRelation('variants', $variantsCollection);

    expect($product->totalStock)->toBe(0);
});

test('priceRange accessor returns same price range when product has price and no variants', function () {
    $product = new Product();
    $product->setAttribute('price', '100.0000');
    $product->setRelation('variants', collect([]));

    expect($product->priceRange)
        ->toBeArray()
        ->toBe(['100.0000', '100.0000']);
});

test('priceRange accessor returns null when product has no price and no variants', function () {
    $product = new Product();
    $product->setAttribute('price', null);
    $product->setRelation('variants', collect([]));

    expect($product->priceRange)->toBeNull();
});

test('priceRange accessor returns variant price range when product has variants', function () {
    $product = new Product();
    $variantsCollection = collect([
        (object) ['price' => '50.0000'],
        (object) ['price' => '150.0000'],
        (object) ['price' => '100.0000'],
    ]);
    $product->setRelation('variants', $variantsCollection);

    expect($product->priceRange)
        ->toBeArray()
        ->toBe(['50.0000', '150.0000']);
});

test('priceRange accessor handles single variant', function () {
    $product = new Product();
    $variantsCollection = collect([
        (object) ['price' => '99.9900'],
    ]);
    $product->setRelation('variants', $variantsCollection);

    expect($product->priceRange)
        ->toBeArray()
        ->toBe(['99.9900', '99.9900']);
});

test('priceRange accessor prioritizes variants over product price when variants exist', function () {
    $product = new Product();
    $product->setAttribute('price', '200.0000');
    $variantsCollection = collect([
        (object) ['price' => '80.0000'],
        (object) ['price' => '120.0000'],
    ]);
    $product->setRelation('variants', $variantsCollection);

    expect($product->priceRange)
        ->toBeArray()
        ->toBe(['80.0000', '120.0000']);
});

test('priceRange accessor filters out null variant prices', function () {
    $product = new Product();
    $variantsCollection = collect([
        (object) ['price' => '75.0000'],
        (object) ['price' => null],
        (object) ['price' => '125.0000'],
        (object) ['price' => ''],
    ]);
    $product->setRelation('variants', $variantsCollection);

    expect($product->priceRange)
        ->toBeArray()
        ->toBe(['75.0000', '125.0000']);
});

test('priceRange accessor returns null when variants exist but have no valid prices', function () {
    $product = new Product();
    $variantsCollection = collect([
        (object) ['price' => null],
        (object) ['price' => ''],
        (object) ['price' => false],
    ]);
    $product->setRelation('variants', $variantsCollection);

    expect($product->priceRange)->toBeNull();
});

test('compareAtPriceRange accessor returns same range when product has compare at price and no variants', function () {
    $product = new Product();
    $product->setAttribute('compare_at_price', '150.0000');
    $product->setRelation('variants', collect([]));

    expect($product->compareAtPriceRange)
        ->toBeArray()
        ->toBe(['150.0000', '150.0000']);
});

test('compareAtPriceRange accessor returns null when product has no compare at price and no variants', function () {
    $product = new Product();
    $product->setAttribute('compare_at_price', null);
    $product->setRelation('variants', collect([]));

    expect($product->compareAtPriceRange)->toBeNull();
});

test('compareAtPriceRange accessor collapses when every variant shares the same compare at price', function () {
    $product = new Product();
    $product->setRelation('variants', collect([
        (object) ['compare_at_price' => '230.9900'],
        (object) ['compare_at_price' => '230.9900'],
    ]));

    expect($product->compareAtPriceRange)
        ->toBeArray()
        ->toBe(['230.9900', '230.9900']);
});

test('compareAtPriceRange accessor returns variant spread when compare at prices differ', function () {
    $product = new Product();
    $product->setRelation('variants', collect([
        (object) ['compare_at_price' => '90.0000'],
        (object) ['compare_at_price' => '210.0000'],
        (object) ['compare_at_price' => '150.0000'],
    ]));

    expect($product->compareAtPriceRange)
        ->toBeArray()
        ->toBe(['90.0000', '210.0000']);
});

test('compareAtPriceRange accessor returns null when only some variants have a compare at price', function () {
    $product = new Product();
    $product->setRelation('variants', collect([
        (object) ['compare_at_price' => '230.9900'],
        (object) ['compare_at_price' => null],
    ]));

    expect($product->compareAtPriceRange)->toBeNull();
});

test('compareAtPriceRange accessor returns null when variants exist but none have a compare at price', function () {
    $product = new Product();
    $product->setAttribute('compare_at_price', '300.0000');
    $product->setRelation('variants', collect([
        (object) ['compare_at_price' => null],
        (object) ['compare_at_price' => ''],
    ]));

    expect($product->compareAtPriceRange)->toBeNull();
});

test('handles decimal precision in price range calculations', function () {
    $product = new Product();
    $variantsCollection = collect([
        (object) ['price' => '19.999'],
        (object) ['price' => '29.001'],
        (object) ['price' => '25.555'],
    ]);
    $product->setRelation('variants', $variantsCollection);

    $priceRange = $product->priceRange;
    expect($priceRange)->toBeArray();
    expect($priceRange[0])->toBe('19.999'); // Min price
    expect($priceRange[1])->toBe('29.001'); // Max price
});

test('priceRange accessor handles large number of variants efficiently', function () {
    $product = new Product();
    $variants = [];
    for ($i = 1; $i <= 100; $i++) {
        $variants[] = (object) ['price' => (string) ($i * 10)];
    }
    $variantsCollection = collect($variants);
    $product->setRelation('variants', $variantsCollection);

    expect($product->priceRange)
        ->toBeArray()
        ->toBe(['10', '1000']);
});

test('isLowStock accessor returns false when track_stock is disabled', function () {
    $product = Product::factory()->create([
        'track_stock' => false,
        'stock' => 5,
        'low_stock_threshold' => 10,
    ]);

    expect($product->is_low_stock)->toBeFalse();
});

test('isLowStock accessor returns false when stock is null', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => null,
        'low_stock_threshold' => 10,
    ]);

    expect($product->is_low_stock)->toBeFalse();
});

test('isLowStock accessor returns false when stock is above threshold', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 15,
        'low_stock_threshold' => 10,
    ]);

    expect($product->is_low_stock)->toBeFalse();
});

test('isLowStock accessor returns true when stock equals threshold', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'low_stock_threshold' => 10,
    ]);

    expect($product->is_low_stock)->toBeTrue();
});

test('isLowStock accessor returns true when stock is below threshold', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 5,
        'low_stock_threshold' => 10,
    ]);

    expect($product->is_low_stock)->toBeTrue();
});
