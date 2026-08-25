<?php

declare(strict_types=1);

use App\Actions\StoreMediaAction;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Models\Media;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;

covers(MediaController::class, StoreMediaRequest::class, StoreMediaAction::class);

uses()->group('media');

test('uploads an image and persists a media record', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('test-image.jpg');

    $response = actingAsSuperAdmin()->postJson(route('admin.media.store'), [
        'file' => $file,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['id', 'type', 'url', 'thumbnail_url', 'mime_type', 'size'])
        ->assertJson(['type' => 'image']);

    assertDatabaseCount('media', 1);
    $media = Media::query()->sole();

    expect($media->disk)->toBe('public')
        ->and($media->path)->toStartWith('media/');

    $filesystem = Storage::disk('public');
    assert($filesystem instanceof FilesystemAdapter);
    $filesystem->assertExists($media->path);
    $filesystem->assertExists($media->thumbnail_path);
});

test('response never leaks the private path or disk', function () {
    Storage::fake('public');

    $response = actingAsSuperAdmin()->postJson(route('admin.media.store'), [
        'file' => UploadedFile::fake()->image('test-image.jpg'),
    ]);

    $response->assertOk()
        ->assertJsonMissingPath('path')
        ->assertJsonMissingPath('disk');
});

test('uploads an image without image processing extension', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', false);

    $response = actingAsSuperAdmin()->postJson(route('admin.media.store'), [
        'file' => UploadedFile::fake()->image('test-image.jpg'),
    ]);

    $response->assertOk()->assertJson(['type' => 'image']);
    expect(Media::query()->sole()->thumbnail_url)->not->toBeNull();
});

test('validates file type', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->create('document.pdf', mimeType: 'application/pdf');

    actingAsSuperAdmin()->postJson(route('admin.media.store'), ['file' => $file])
        ->assertStatus(422)
        ->assertInvalid('file');
});

test('requires authentication', function () {
    Storage::fake('public');

    postJson(route('admin.media.store'), [
        'file' => UploadedFile::fake()->image('test-image.jpg'),
    ])->assertUnauthorized();
});

test('skips thumbnail generation when generate_thumbnail is false', function () {
    Storage::fake('public');

    actingAsSuperAdmin()->postJson(route('admin.media.store'), [
        'file' => UploadedFile::fake()->image('test-image.jpg'),
        'generate_thumbnail' => false,
    ])->assertOk();

    expect(Media::query()->sole()->thumbnail_path)->toBeNull();
});
