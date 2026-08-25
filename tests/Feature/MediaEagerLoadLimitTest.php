<?php

declare(strict_types=1);

use App\Actions\SyncMediaAction;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Queries\StorefrontProductListQuery;
use Illuminate\Support\Facades\DB;

covers(StorefrontProductListQuery::class);

uses()->group('media', 'performance');

function createSimpleProductWithGalleryMedia(int $mediaCount): Product
{
    $product = Product::factory()->active()->create();

    $mediaIds = Media::factory()->count($mediaCount)->create()->pluck('id')->all();

    (new SyncMediaAction())->handle($product, $mediaIds);

    return $product;
}

function createProductWithImagelessDefaultVariantAndGalleryMedia(int $mediaCount): Product
{
    $product = Product::factory()->active()->create();

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
    ]);

    $mediaIds = Media::factory()->count($mediaCount)->create()->pluck('id')->all();

    (new SyncMediaAction())->handle($product, $mediaIds);

    return $product;
}

test('batches media eager loads for simple products without scaling query count', function (): void {
    app(StorefrontProductListQuery::class)->execute();

    for ($i = 0; $i < 3; $i++) {
        createSimpleProductWithGalleryMedia(3);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $result = app(StorefrontProductListQuery::class)->execute();
    $smallQueryLog = DB::getQueryLog();
    DB::flushQueryLog();
    DB::disableQueryLog();

    expect($result)->toHaveCount(3);

    for ($i = 0; $i < 3; $i++) {
        createSimpleProductWithGalleryMedia(10);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $result = app(StorefrontProductListQuery::class)->execute();
    $largeQueryLog = DB::getQueryLog();
    DB::flushQueryLog();
    DB::disableQueryLog();

    expect($result)->toHaveCount(6)
        ->and(count($largeQueryLog))->toBe(count($smallQueryLog))
        ->and(count($smallQueryLog))->toBeLessThanOrEqual(10);
});

test('batches media eager loads for products whose default variant has no image', function (): void {
    app(StorefrontProductListQuery::class)->execute();

    for ($i = 0; $i < 3; $i++) {
        createProductWithImagelessDefaultVariantAndGalleryMedia(3);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $result = app(StorefrontProductListQuery::class)->execute();
    $smallQueryLog = DB::getQueryLog();
    DB::flushQueryLog();
    DB::disableQueryLog();

    expect($result)->toHaveCount(3);

    for ($i = 0; $i < 3; $i++) {
        createProductWithImagelessDefaultVariantAndGalleryMedia(10);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $result = app(StorefrontProductListQuery::class)->execute();
    $largeQueryLog = DB::getQueryLog();
    DB::flushQueryLog();
    DB::disableQueryLog();

    expect($result)->toHaveCount(6)
        ->and(count($largeQueryLog))->toBe(count($smallQueryLog))
        ->and(count($smallQueryLog))->toBeLessThanOrEqual(10);
});
