<?php

declare(strict_types=1);

use App\Actions\UpsertProductDownloadsAction;
use App\DTOs\ProductDownloadInput;
use App\Models\Media;
use App\Models\OrderItemDownload;
use App\Models\Product;
use App\Models\ProductDownload;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

covers(UpsertProductDownloadsAction::class, ProductDownloadInput::class);

uses()->group('actions', 'product');

/**
 * @param  array<string, mixed>  $overrides
 */
function downloadInput(array $overrides = []): ProductDownloadInput
{
    return ProductDownloadInput::fromArray(array_merge([
        'name' => 'Manual',
        'media_id' => Media::factory()->file()->create()->id,
    ], $overrides));
}

test('creates downloads for a product', function () {
    $product = Product::factory()->digital()->create();

    app(UpsertProductDownloadsAction::class)->handle($product, [
        downloadInput(['name' => 'First']),
        downloadInput(['name' => 'Second']),
    ]);

    assertDatabaseCount('product_downloads', 2);
    assertDatabaseHas('product_downloads', ['product_id' => $product->id, 'name' => 'First', 'sort_order' => 0]);
    assertDatabaseHas('product_downloads', ['product_id' => $product->id, 'name' => 'Second', 'sort_order' => 1]);
});

test('falls back to the media original filename when name is empty', function () {
    $product = Product::factory()->digital()->create();
    $media = Media::factory()->file()->create(['original_filename' => 'guide.pdf']);

    app(UpsertProductDownloadsAction::class)->handle($product, [
        downloadInput(['name' => '', 'media_id' => $media->id]),
    ]);

    assertDatabaseHas('product_downloads', ['product_id' => $product->id, 'name' => 'guide.pdf', 'media_id' => $media->id]);
});

test('updates an existing download by id and reorders by array position', function () {
    $product = Product::factory()->digital()->create();
    $existing = ProductDownload::factory()->create([
        'product_id' => $product->id,
        'name' => 'Old name',
        'sort_order' => 0,
    ]);
    $second = ProductDownload::factory()->create([
        'product_id' => $product->id,
        'name' => 'Second',
        'sort_order' => 1,
    ]);

    app(UpsertProductDownloadsAction::class)->handle($product, [
        downloadInput(['id' => $second->id, 'name' => 'Second', 'media_id' => $second->media_id]),
        downloadInput(['id' => $existing->id, 'name' => 'New name', 'media_id' => $existing->media_id]),
    ]);

    assertDatabaseCount('product_downloads', 2);
    assertDatabaseHas('product_downloads', ['id' => $existing->id, 'name' => 'New name', 'sort_order' => 1]);
    assertDatabaseHas('product_downloads', ['id' => $second->id, 'name' => 'Second', 'sort_order' => 0]);
});

test('updates the media reference for an existing download without touching the previous media row', function () {
    $product = Product::factory()->digital()->create();
    $oldMedia = Media::factory()->file()->create();
    $newMedia = Media::factory()->file()->create();
    $download = ProductDownload::factory()->create(['product_id' => $product->id, 'media_id' => $oldMedia->id]);

    app(UpsertProductDownloadsAction::class)->handle($product, [
        downloadInput(['id' => $download->id, 'name' => $download->name, 'media_id' => $newMedia->id]),
    ]);

    assertDatabaseHas('product_downloads', ['id' => $download->id, 'media_id' => $newMedia->id]);
    assertDatabaseHas('media', ['id' => $oldMedia->id]);
});

test('keeps a variant id that belongs to the product and nulls a foreign one', function () {
    $product = Product::factory()->digital()->create();
    $ownVariant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $otherProduct = Product::factory()->create();
    $foreignVariant = ProductVariant::factory()->create(['product_id' => $otherProduct->id]);

    app(UpsertProductDownloadsAction::class)->handle($product, [
        downloadInput(['name' => 'For own variant', 'variant_id' => $ownVariant->id]),
        downloadInput(['name' => 'For foreign variant', 'variant_id' => $foreignVariant->id]),
    ]);

    assertDatabaseHas('product_downloads', ['name' => 'For own variant', 'product_variant_id' => $ownVariant->id]);
    assertDatabaseHas('product_downloads', ['name' => 'For foreign variant', 'product_variant_id' => null]);
});

test('removes orphaned downloads and deletes their now-unreferenced media', function () {
    $product = Product::factory()->digital()->create();
    $kept = ProductDownload::factory()->create(['product_id' => $product->id]);
    $orphan = ProductDownload::factory()->create(['product_id' => $product->id]);

    app(UpsertProductDownloadsAction::class)->handle($product, [
        downloadInput(['id' => $kept->id, 'name' => $kept->name, 'media_id' => $kept->media_id]),
    ]);

    assertDatabaseHas('product_downloads', ['id' => $kept->id]);
    assertDatabaseMissing('product_downloads', ['id' => $orphan->id]);
    assertDatabaseHas('media', ['id' => $kept->media_id]);
    assertDatabaseMissing('media', ['id' => $orphan->media_id]);
});

test('removes the orphaned download while leaving an already-issued grant snapshot intact', function () {
    $product = Product::factory()->digital()->create();
    $media = Media::factory()->file()->create();
    $orphan = ProductDownload::factory()->create(['product_id' => $product->id, 'media_id' => $media->id]);

    $grant = OrderItemDownload::factory()->create([
        'product_download_id' => $orphan->id,
        'media_id' => $media->id,
        'file_path' => 'downloads/' . Str::random(40) . '.zip',
        'original_filename' => 'manual.zip',
        'file_size' => 2048,
    ]);

    app(UpsertProductDownloadsAction::class)->handle($product, []);

    assertDatabaseMissing('product_downloads', ['id' => $orphan->id]);
    assertDatabaseHas('order_item_downloads', [
        'id' => $grant->id,
        'product_download_id' => null,
        'file_path' => $grant->file_path,
        'original_filename' => $grant->original_filename,
    ]);
    assertDatabaseHas('media', ['id' => $media->id]);
});

test('removes all downloads when given an empty list', function () {
    $product = Product::factory()->digital()->create();
    $download = ProductDownload::factory()->create(['product_id' => $product->id]);

    app(UpsertProductDownloadsAction::class)->handle($product, []);

    assertDatabaseCount('product_downloads', 0);
    assertDatabaseMissing('media', ['id' => $download->media_id]);
});
