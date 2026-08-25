<?php

declare(strict_types=1);

use App\Enums\MediaType;
use App\Enums\SettingType;
use App\Models\Media;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

covers(Media::class);

uses()->group('models', 'media');

test('serializes to the frontend media item shape', function () {
    $media = Media::factory()->uploaded()->create();

    $array = $media->toArray();

    expect($array)->toHaveKeys(['id', 'type', 'url', 'thumbnail_url', 'mime_type', 'size'])
        ->and($array)->not->toHaveKeys(['disk', 'path', 'thumbnail_path'])
        ->and($array['type'])->toBe('image');
});

test('exposes the alt text and defaults it to null', function () {
    $withAlt = Media::factory()->uploaded()->create(['alt' => 'A red running shoe']);
    $withoutAlt = Media::factory()->uploaded()->create();

    expect($withAlt->toArray())->toHaveKey('alt')
        ->and($withAlt->alt)->toBe('A red running shoe')
        ->and($withoutAlt->alt)->toBeNull()
        ->and(Media::displayColumns())->toContain('media.alt');
});

test('exposes the intrinsic dimensions galleries size themselves from', function () {
    $media = Media::factory()->uploaded()->create(['width' => 1200, 'height' => 1600]);

    expect($media->toArray())->toHaveKeys(['width', 'height'])
        ->and($media->width)->toBe(1200)
        ->and($media->height)->toBe(1600)
        ->and(Media::displayColumns())->toContain('media.width', 'media.height');
});

test('displaySelect mirrors displayColumns unqualified for the eager-load shortcut', function () {
    $unqualified = array_map(fn (string $column): string => str_replace('media.', '', $column), Media::displayColumns());

    expect(Media::displaySelect())->toBe(implode(',', $unqualified))
        ->and(Media::displaySelect())->not->toContain('media.');
});

test('builds public url from stored path', function () {
    $media = Media::factory()->uploaded()->create(['path' => 'images/photo.webp']);

    expect($media->url)->toBe(Storage::disk('public')->url('images/photo.webp'));
});

test('passes through an external url verbatim', function () {
    $media = Media::factory()->create(['external_url' => 'https://example.test/a.jpg']);

    expect($media->url)->toBe('https://example.test/a.jpg');
});

test('never exposes a url for a private file', function () {
    $media = Media::factory()->file()->create();

    expect($media->url)->toBeNull()
        ->and($media->thumbnail_url)->toBeNull()
        ->and($media->type)->toBe(MediaType::File);
});

test('thumbnail url falls back to the image url when no thumbnail exists', function () {
    $media = Media::factory()->create(['external_url' => 'https://example.test/a.jpg', 'thumbnail_path' => null]);

    expect($media->thumbnail_url)->toBe('https://example.test/a.jpg');
});

test('deletes unreferenced media', function () {
    $media = Media::factory()->create();

    Media::deleteUnreferenced([$media->id]);

    expect(Media::query()->whereKey($media->id)->exists())->toBeFalse();
});

test('keeps media attached to a model', function () {
    $brand = App\Models\Brand::factory()->create();
    $media = Media::factory()->create();
    (new App\Actions\SyncMediaAction())->handle($brand, [$media->id]);

    Media::deleteUnreferenced([$media->id]);

    expect(Media::query()->whereKey($media->id)->exists())->toBeTrue();
});

test('keeps media referenced by an asset setting', function () {
    $media = Media::factory()->create();
    Setting::factory()->create(['type' => SettingType::Asset, 'value' => (string) $media->id]);

    Media::deleteUnreferenced([$media->id]);

    expect(Media::query()->whereKey($media->id)->exists())->toBeTrue();
});

test('keeps media snapshotted on an order item', function () {
    $media = Media::factory()->uploaded()->create();
    App\Models\OrderItem::factory()->create(['media_id' => $media->id]);

    Media::deleteUnreferenced([$media->id]);

    expect(Media::query()->whereKey($media->id)->exists())->toBeTrue();
});

test('deleting media removes the stored files on the correct disk', function () {
    Storage::fake('public');
    Storage::disk('public')->put('images/x.webp', 'a');
    Storage::disk('public')->put('thumbnails/x.webp', 'b');
    Storage::disk('public')->put('thumbnails/small-x.webp', 'c');

    $media = Media::factory()->create([
        'disk' => 'public',
        'path' => 'images/x.webp',
        'thumbnail_path' => 'thumbnails/x.webp',
        'small_thumbnail_path' => 'thumbnails/small-x.webp',
        'external_url' => null,
    ]);

    $media->delete();

    Storage::disk('public')->assertMissing('images/x.webp');
    Storage::disk('public')->assertMissing('thumbnails/x.webp');
    Storage::disk('public')->assertMissing('thumbnails/small-x.webp');
});

test('small thumbnail url falls back to the thumbnail when no small derivative exists', function () {
    Storage::fake('public');

    $media = Media::factory()->create([
        'disk' => 'public',
        'path' => 'images/x.webp',
        'thumbnail_path' => 'thumbnails/x.webp',
        'small_thumbnail_path' => null,
        'external_url' => null,
    ]);

    expect($media->small_thumbnail_url)->toBe($media->thumbnail_url)
        ->and($media->small_thumbnail_url)->toContain('thumbnails/x.webp');
});

test('small thumbnail url uses the small derivative when it exists', function () {
    Storage::fake('public');

    $media = Media::factory()->create([
        'disk' => 'public',
        'path' => 'images/x.webp',
        'thumbnail_path' => 'thumbnails/x.webp',
        'small_thumbnail_path' => 'thumbnails/small-x.webp',
        'external_url' => null,
    ]);

    expect($media->small_thumbnail_url)->toContain('thumbnails/small-x.webp');
});
