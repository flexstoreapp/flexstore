<?php

declare(strict_types=1);

use App\Actions\BulkDestroyBrandAction;
use App\Models\Brand;
use App\Models\Media;
use App\Models\Product;

covers(BulkDestroyBrandAction::class);

uses()->group('actions', 'brand');

test('deletes multiple brands by their ids', function () {
    Brand::factory()->count(5)->create();
    $brandIds = Brand::query()->limit(3)->pluck('id')->all();

    $action = new BulkDestroyBrandAction();
    $result = $action->handle($brandIds);

    expect($result)->toBe(3)
        ->and(Brand::query()->count())->toBe(2)
        ->and(Brand::query()->whereIn('id', $brandIds)->count())->toBe(0);
});

test('returns zero when no brands found for deletion', function () {
    Brand::factory()->count(5)->create();
    $nonExistingIds = [999, 1000];

    $action = new BulkDestroyBrandAction();
    $result = $action->handle($nonExistingIds);

    expect($result)->toBe(0)
        ->and(Brand::query()->count())->toBe(5);
});

test('nullifies product brand_id when brand is deleted', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    expect($product->brand_id)->toBe($brand->id);

    $action = new BulkDestroyBrandAction();
    $action->handle([$brand->id]);

    $product->refresh();

    expect($product->brand_id)->toBeNull()
        ->and(Brand::query()->count())->toBe(0);
});

test('deletes brand logo media that is no longer referenced', function () {
    $media = Media::factory()->create();
    $brand = Brand::factory()->create(['image_id' => $media->id]);

    (new BulkDestroyBrandAction())->handle([$brand->id]);

    expect(Media::query()->whereKey($media->id)->exists())->toBeFalse();
});

test('keeps brand logo media still referenced by another brand', function () {
    $media = Media::factory()->create();
    $deleted = Brand::factory()->create(['image_id' => $media->id]);
    Brand::factory()->create(['image_id' => $media->id]);

    (new BulkDestroyBrandAction())->handle([$deleted->id]);

    expect(Media::query()->whereKey($media->id)->exists())->toBeTrue();
});
