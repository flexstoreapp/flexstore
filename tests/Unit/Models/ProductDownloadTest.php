<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\ProductDownload;
use Illuminate\Support\Str;

covers(ProductDownload::class);

uses()->group('models', 'product');

test('casts the sort order to an integer', function () {
    expect((new ProductDownload())->casts())->toHaveKey('sort_order', 'integer');
});

test('uses a uuid primary key', function () {
    $download = ProductDownload::factory()->create();

    expect($download->getIncrementing())->toBeFalse()
        ->and(Str::isUuid($download->id))->toBeTrue();
});

test('belongs to a media record', function () {
    $media = Media::factory()->create();
    $download = ProductDownload::factory()->create(['media_id' => $media->id]);

    expect($download->media)->toBeInstanceOf(Media::class)
        ->and($download->media->id)->toBe($media->id);
});
