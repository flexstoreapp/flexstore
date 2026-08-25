<?php

declare(strict_types=1);

use App\Actions\SyncMediaAction;
use App\Concerns\HasMedia;
use App\Models\Media;
use App\Models\Product;
use App\Utilities\StringIdMorphToMany;

covers(StringIdMorphToMany::class, HasMedia::class);

uses()->group('media');

test('media gallery relation binds integer parent ids as strings', function () {
    $media = Media::factory()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$media->id]);

    expect($product->mediaGallery())->toBeInstanceOf(StringIdMorphToMany::class)
        ->and($product->mediaGallery()->pluck('media.id')->all())->toBe([$media->id]);
});
