<?php

declare(strict_types=1);

use App\Actions\DuplicateProductAction;
use App\Actions\SyncMediaAction;
use App\DTOs\DuplicateProductInput;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductDownload;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;

covers(DuplicateProductAction::class, DuplicateProductInput::class);

uses()->group('actions', 'product');

test('duplicates product with SKUs and barcodes when option enabled', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product',
        'sku' => 'ORIGINAL-SKU',
        'barcode' => 'ORIGINAL-BARCODE',
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_skus' => true,
        'duplicate_barcodes' => true,
    ]));

    expect($duplicated->sku)->toBe('ORIGINAL-SKU-copy')
        ->and($duplicated->barcode)->toBe('ORIGINAL-BARCODE-copy');
});

test('generates unique URL handle when duplicate exists', function () {
    $existingProduct = Product::factory()->create(['url_handle' => 'test-product']);
    $existingProduct2 = Product::factory()->create(['url_handle' => 'test-product-1']);

    $product = Product::factory()->create([
        'title' => 'Test Product',
        'url_handle' => 'test-product-original',
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Test Product',
    ]));

    expect($duplicated->url_handle)->toBe('test-product-2');
});

test('uses provided URL handle when given', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product',
        'url_handle' => 'original-product',
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'url_handle' => 'custom-url-handle',
    ]));

    expect($duplicated->url_handle)->toBe('custom-url-handle');
});

test('duplicates product with options and variants', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product',
    ]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
    ]);

    $smallValue = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'Small',
    ]);

    $largeValue = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'Large',
    ]);

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'title' => 'Small',
        'sku' => 'VAR-SKU-1',
        'barcode' => 'VAR-BARCODE-1',
    ]);

    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'title' => 'Large',
        'sku' => 'VAR-SKU-2',
        'barcode' => 'VAR-BARCODE-2',
    ]);

    ProductVariantOption::factory()->create([
        'product_variant_id' => $variant1->id,
        'product_option_id' => $sizeOption->id,
        'product_option_value_id' => $smallValue->id,
    ]);

    ProductVariantOption::factory()->create([
        'product_variant_id' => $variant2->id,
        'product_option_id' => $sizeOption->id,
        'product_option_value_id' => $largeValue->id,
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
    ]));

    expect($duplicated->options)->toHaveCount(1)
        ->and($duplicated->options->first()->name)->toBe('Size')
        ->and($duplicated->options->first()->values)->toHaveCount(2)
        ->and($duplicated->variants)->toHaveCount(2)
        ->and($duplicated->variants->pluck('title')->toArray())->toBe(['Small', 'Large'])
        ->and($duplicated->variants->first()->sku)->toBeNull()
        ->and($duplicated->variants->first()->barcode)->toBeNull();
});

test('duplicates variants with SKUs and barcodes when option enabled', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product',
    ]);

    $option = ProductOption::factory()->create(['product_id' => $product->id]);
    $value = ProductOptionValue::factory()->create(['product_option_id' => $option->id]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'VAR-SKU',
        'barcode' => 'VAR-BARCODE',
    ]);

    ProductVariantOption::factory()->create([
        'product_variant_id' => $variant->id,
        'product_option_id' => $option->id,
        'product_option_value_id' => $value->id,
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_skus' => true,
        'duplicate_barcodes' => true,
    ]));

    $duplicatedVariant = $duplicated->variants->first();
    expect($duplicatedVariant->sku)->toBe('VAR-SKU-copy')
        ->and($duplicatedVariant->barcode)->toBe('VAR-BARCODE-copy');
});

test('handles external image URLs by sharing the media row', function () {
    $media = Media::factory()->create(['external_url' => 'https://picsum.photos/400/400']);
    $product = Product::factory()->create([
        'title' => 'Original Product',
    ]);
    (new SyncMediaAction())->handle($product, [$media->id]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_media' => true,
    ]));

    expect($duplicated->mediaGallery)->toHaveCount(1)
        ->and($duplicated->mediaGallery->first()->id)->toBe($media->id)
        ->and($duplicated->mediaGallery->first()->url)->toBe('https://picsum.photos/400/400');
});

test('handles variant external image URLs by sharing the media row', function () {
    $media = Media::factory()->create(['external_url' => 'https://picsum.photos/400/400']);
    $product = Product::factory()->create([
        'title' => 'Original Product',
    ]);

    $option = ProductOption::factory()->create(['product_id' => $product->id]);
    $value = ProductOptionValue::factory()->create(['product_option_id' => $option->id]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);
    $variant->update(['media_id' => $media->id]);

    ProductVariantOption::factory()->create([
        'product_variant_id' => $variant->id,
        'product_option_id' => $option->id,
        'product_option_value_id' => $value->id,
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_media' => true,
    ]));

    $duplicatedVariant = $duplicated->variants->first();
    expect($duplicatedVariant->media?->id)->toBe($media->id)
        ->and($duplicatedVariant->media?->url)->toBe('https://picsum.photos/400/400');
});

test('handles product without variants inventory correctly', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product',
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
        'low_stock_threshold' => 5,
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_inventory' => true,
    ]));

    expect($duplicated->stock)->toBe(10)
        ->and($duplicated->in_stock)->toBeTrue()
        ->and($duplicated->low_stock_threshold)->toBe(5);
});

test('handles product with variants inventory correctly', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product',
        'track_stock' => true,
        'stock' => 10,
    ]);

    $option = ProductOption::factory()->create(['product_id' => $product->id]);
    $value = ProductOptionValue::factory()->create(['product_option_id' => $option->id]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => 5,
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
    ]));

    expect($duplicated->stock)->toBeNull()
        ->and($duplicated->in_stock)->toBeNull()
        ->and($duplicated->track_stock)->toBeNull();
});

test('generates unique SKU when duplicate exists', function () {
    Product::factory()->create(['sku' => 'TEST-SKU-copy']);
    Product::factory()->create(['sku' => 'TEST-SKU-copy-1']);

    $product = Product::factory()->create(['sku' => 'TEST-SKU']);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_skus' => true,
    ]));

    expect($duplicated->sku)->toBe('TEST-SKU-copy-2');
});

test('generates unique barcode when duplicate exists', function () {
    Product::factory()->create(['barcode' => 'TEST-BARCODE-copy']);
    Product::factory()->create(['barcode' => 'TEST-BARCODE-copy-1']);

    $product = Product::factory()->create(['barcode' => 'TEST-BARCODE']);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_barcodes' => true,
    ]));

    expect($duplicated->barcode)->toBe('TEST-BARCODE-copy-2');
});

test('handles product without optional fields', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product',
        'category_id' => null,
        'brand_id' => null,
        'tax_category' => null,
        'is_tax_exempt' => true,
        'sku' => null,
        'barcode' => null,
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
    ]));

    expect($duplicated->category_id)->toBeNull()
        ->and($duplicated->brand_id)->toBeNull()
        ->and($duplicated->tax_category)->toBeNull()
        ->and($duplicated->sku)->toBeNull()
        ->and($duplicated->barcode)->toBeNull()
        ->and($duplicated->mediaGallery)->toBeEmpty();
});

test('preserves variant pricing when duplicate_pricing is false', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product',
    ]);

    $option = ProductOption::factory()->create(['product_id' => $product->id]);
    $value = ProductOptionValue::factory()->create(['product_option_id' => $option->id]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '99.9900',
        'compare_at_price' => '119.9900',
        'cost_per_item' => '50.0000',
    ]);

    ProductVariantOption::factory()->create([
        'product_variant_id' => $variant->id,
        'product_option_id' => $option->id,
        'product_option_value_id' => $value->id,
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_pricing' => false,
    ]));

    $duplicatedVariant = $duplicated->variants->first();
    expect($duplicatedVariant->price)->toBe('99.9900')
        ->and($duplicatedVariant->compare_at_price)->toBeNull()
        ->and($duplicatedVariant->cost_per_item)->toBeNull();
});

test('handles translatable fields correctly', function () {
    $product = Product::factory()->create([
        'title' => ['en' => 'English Title', 'fr' => 'French Title'],
        'description' => ['en' => 'English Description', 'fr' => 'French Description'],
        'seo_title' => ['en' => 'English SEO', 'fr' => 'French SEO'],
        'seo_description' => ['en' => 'English SEO Desc', 'fr' => 'French SEO Desc'],
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'New Title',
        'duplicate_seo' => true,
    ]));

    expect($duplicated->getTranslation('title', 'en'))->toBe('New Title')
        ->and($duplicated->getTranslation('description', 'en'))->toBe('English Description')
        ->and($duplicated->getTranslation('description', 'fr'))->toBe('French Description')
        ->and($duplicated->getTranslation('seo_title', 'en'))->toBe('English SEO')
        ->and($duplicated->getTranslation('seo_title', 'fr'))->toBe('French SEO')
        ->and($duplicated->getTranslation('seo_description', 'en'))->toBe('English SEO Desc')
        ->and($duplicated->getTranslation('seo_description', 'fr'))->toBe('French SEO Desc');
});

test('shares variant media with the duplicate', function () {
    $media = Media::factory()->uploaded()->create();
    $product = Product::factory()->create([
        'title' => 'Original Product',
    ]);

    $option = ProductOption::factory()->create(['product_id' => $product->id]);
    $value = ProductOptionValue::factory()->create(['product_option_id' => $option->id]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);
    $variant->update(['media_id' => $media->id]);

    ProductVariantOption::factory()->create([
        'product_variant_id' => $variant->id,
        'product_option_id' => $option->id,
        'product_option_value_id' => $value->id,
    ]);

    $action = app(DuplicateProductAction::class);
    $duplicated = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_media' => true,
    ]));

    $duplicatedVariant = $duplicated->variants->first();

    expect($duplicatedVariant->media?->id)->toBe($media->id);
});

test('shares product media with the duplicate', function () {
    $media = Media::factory()->uploaded()->create();
    $product = Product::factory()->create([
        'title' => 'Original Product',
    ]);
    (new SyncMediaAction())->handle($product, [$media->id]);

    $action = app(DuplicateProductAction::class);
    $result = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_media' => true,
        'duplicate_pricing' => true,
        'duplicate_inventory' => true,
        'duplicate_skus' => true,
        'duplicate_barcodes' => true,
    ]));

    expect($result->mediaGallery->pluck('id')->all())->toBe([$media->id]);
});

test('handles variant inventory with low stock threshold reset', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product',
        'stock' => 100,
        'track_stock' => true,
        'in_stock' => true,
        'low_stock_threshold' => 10,
    ]);

    ProductVariant::factory()->for($product)->create([
        'stock' => 50,
        'track_stock' => true,
        'in_stock' => true,
        'low_stock_threshold' => 5,
    ]);

    $action = app(DuplicateProductAction::class);
    $result = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_images' => false,
        'duplicate_media' => false,
        'duplicate_pricing' => false,
        'duplicate_inventory' => false,
        'duplicate_skus' => false,
        'duplicate_barcodes' => false,
    ]));

    $result->load('variants');

    expect($result->stock)->toBeNull()
        ->and($result->in_stock)->toBeNull()
        ->and($result->track_stock)->toBeNull()
        ->and($result->low_stock_threshold)->toBeNull()
        ->and($result->variants->first()->stock)->toBe(0)
        ->and($result->variants->first()->in_stock)->toBeFalse()
        ->and($result->variants->first()->low_stock_threshold)->toBeNull();
});

test('generates unique variant barcodes with existing conflicts', function () {
    Product::factory()->create([
        'title' => 'Another Product',
        'barcode' => '9999999999',
    ]);

    $product = Product::factory()->create([
        'title' => 'Original Product',
        'barcode' => '9999999999',
    ]);

    $variant = ProductVariant::factory()->for($product)->create([
        'barcode' => '8888888888',
    ]);

    Product::factory()->create([
        'barcode' => '8888888888',
    ]);

    $action = app(DuplicateProductAction::class);
    $result = $action->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicated Product',
        'duplicate_images' => false,
        'duplicate_media' => false,
        'duplicate_pricing' => false,
        'duplicate_inventory' => false,
        'duplicate_skus' => false,
        'duplicate_barcodes' => true,
    ]));

    $result->load('variants');

    expect($result->barcode)->not->toBe('9999999999')
        ->and($result->variants->first()->barcode)->not->toBe('8888888888')
        ->and($result->barcode)->not->toBe('8888888888')
        ->and($result->variants->first()->barcode)->not->toBe('9999999999');
});

test('duplicates a digital product and shares its download media', function () {
    $media = Media::factory()->file()->create();
    $product = Product::factory()->digital()->create(['title' => 'Original']);
    ProductDownload::factory()->create([
        'product_id' => $product->id,
        'media_id' => $media->id,
        'name' => 'Manual',
    ]);

    $result = app(DuplicateProductAction::class)->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicate',
        'duplicate_digital_files' => true,
    ]));

    $clone = $result->downloads()->first();

    expect($clone)->not->toBeNull()
        ->and($clone->name)->toBe('Manual')
        ->and($clone->media_id)->toBe($media->id);
});

test('shares download media without creating a new media row', function () {
    $media = Media::factory()->file()->create();
    $product = Product::factory()->digital()->create(['title' => 'Original']);
    ProductDownload::factory()->create([
        'product_id' => $product->id,
        'media_id' => $media->id,
    ]);

    app(DuplicateProductAction::class)->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicate',
        'duplicate_digital_files' => true,
    ]));

    expect(Media::query()->count())->toBe(1)
        ->and(ProductDownload::query()->count())->toBe(2);
});

test('skips digital files when not selected', function () {
    $media = Media::factory()->file()->create();
    $product = Product::factory()->digital()->create(['title' => 'Original']);
    ProductDownload::factory()->create([
        'product_id' => $product->id,
        'media_id' => $media->id,
    ]);

    $result = app(DuplicateProductAction::class)->handle($product, DuplicateProductInput::fromArray([
        'title' => 'Duplicate',
        'duplicate_digital_files' => false,
    ]));

    expect($result->downloads()->count())->toBe(0)
        ->and($product->downloads()->count())->toBe(1);
});
