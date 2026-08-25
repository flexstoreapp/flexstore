<?php

declare(strict_types=1);

use App\Actions\BulkDestroyProductAction;
use App\Models\Media;
use App\Models\Mediable;
use App\Models\Product;
use App\Models\ProductVariant;

covers(BulkDestroyProductAction::class);

uses()->group('actions', 'product');

test('deletes multiple products by their ids', function () {
    Product::factory()->count(5)->create();
    $productIds = Product::query()->limit(3)->pluck('id')->all();

    $action = app(BulkDestroyProductAction::class);
    $result = $action->handle($productIds);

    expect($result)->toBe(3)
        ->and(Product::query()->count())->toBe(2)
        ->and(Product::query()->whereIn('id', $productIds)->count())->toBe(0);
});

test('returns zero when no products found for deletion', function () {
    Product::factory()->count(5)->create();
    $nonExistingIds = [999, 1000];

    $action = app(BulkDestroyProductAction::class);
    $result = $action->handle($nonExistingIds);

    expect($result)->toBe(0)
        ->and(Product::query()->count())->toBe(5);
});

test('deletes product gallery media when products are bulk destroyed', function () {
    $product1 = Product::factory()->withMedia(2)->create();
    $product2 = Product::factory()->withMedia(1)->create();
    $product3 = Product::factory()->create();

    $productIds = [$product1->id, $product2->id, $product3->id];

    expect(Mediable::query()->count())->toBe(3)
        ->and(Media::query()->count())->toBe(3);

    $action = app(BulkDestroyProductAction::class);
    $result = $action->handle($productIds);

    expect($result)->toBe(3)
        ->and(Product::query()->count())->toBe(0)
        ->and(Mediable::query()->count())->toBe(0)
        ->and(Media::query()->count())->toBe(0);
});

test('keeps gallery media still shared with another product', function () {
    $original = Product::factory()->withMedia(1)->create();
    $sharedMediaId = (int) $original->mediaGallery->first()->id;

    $duplicate = Product::factory()->create();
    (new App\Actions\SyncMediaAction())->handle($duplicate, [$sharedMediaId]);

    app(BulkDestroyProductAction::class)->handle([$original->id]);

    expect(Media::query()->whereKey($sharedMediaId)->exists())->toBeTrue();
});

test('keeps gallery media still referenced by an order item', function () {
    $product = Product::factory()->withMedia(1)->create();
    $mediaId = (int) $product->mediaGallery->first()->id;
    App\Models\OrderItem::factory()->create(['media_id' => $mediaId]);

    app(BulkDestroyProductAction::class)->handle([$product->id]);

    expect(Media::query()->whereKey($mediaId)->exists())->toBeTrue();
});

test('bulk destroys products with variants that have media', function () {
    $product1 = Product::factory()->create();
    ProductVariant::factory()->create(['product_id' => $product1->id, 'media_id' => Media::factory()->create()->id]);
    ProductVariant::factory()->create(['product_id' => $product1->id, 'media_id' => Media::factory()->create()->id]);

    $product2 = Product::factory()->create();
    ProductVariant::factory()->create(['product_id' => $product2->id]);

    $result = app(BulkDestroyProductAction::class)->handle([$product1->id, $product2->id]);

    expect($result)->toBe(2)
        ->and(Product::query()->count())->toBe(0)
        ->and(ProductVariant::query()->count())->toBe(0);
});

test('bulk destroys products with both product and variant media', function () {
    $product = Product::factory()->withMedia(1)->create();
    $variantMediaId = Media::factory()->create()->id;
    ProductVariant::factory()->create(['product_id' => $product->id, 'media_id' => $variantMediaId]);

    expect(Media::query()->count())->toBe(2);

    $result = app(BulkDestroyProductAction::class)->handle([$product->id]);

    expect($result)->toBe(1)
        ->and(Product::query()->count())->toBe(0)
        ->and(ProductVariant::query()->count())->toBe(0)
        ->and(Media::query()->count())->toBe(0);
});

test('releases the media behind a deleted digital product download files', function () {
    $product = Product::factory()->digital()->create();
    $media = Media::factory()->file()->create();
    App\Models\ProductDownload::factory()->create([
        'product_id' => $product->id,
        'media_id' => $media->id,
    ]);

    app(BulkDestroyProductAction::class)->handle([$product->id]);

    expect(Media::query()->whereKey($media->id)->exists())->toBeFalse();
});
