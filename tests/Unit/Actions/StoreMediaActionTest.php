<?php

declare(strict_types=1);

use App\Actions\StoreMediaAction;
use App\Enums\MediaType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

covers(StoreMediaAction::class);

uses()->group('actions', 'media');

test('stores uploaded file as raw when image extension is not loaded', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', false);

    $file = UploadedFile::fake()->create('image.jpg', 1000, 'image/jpeg');

    $media = (new StoreMediaAction())->handle($file);

    Storage::disk('public')->assertExists($media->path);
    Storage::disk('public')->assertExists($media->thumbnail_path);
    expect($media->type)->toBe(MediaType::Image)
        ->and($media->disk)->toBe('public')
        ->and($media->path)->toStartWith('media/')
        ->and($media->thumbnail_path)->toStartWith('thumbnails/')
        ->and($media->original_filename)->toBe('image.jpg');
});

test('stores uploaded svg file as raw regardless of extension loaded', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $file = UploadedFile::fake()->create('image.svg', 100, 'image/svg+xml');

    $media = (new StoreMediaAction())->handle($file);

    Storage::disk('public')->assertExists($media->path);
    Storage::disk('public')->assertExists($media->thumbnail_path);
    expect($media->path)->toStartWith('media/')
        ->and($media->thumbnail_path)->toStartWith('thumbnails/');
});

test('processes uploaded file to webp when image extension is loaded', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $manager = new ImageManager(new Driver());
    $image = $manager->createImage(120, 80)->fill('red');
    $file = UploadedFile::fake()->createWithContent('image.jpg', (string) $image->encode(new JpegEncoder()));

    $media = (new StoreMediaAction())->handle($file);

    Storage::disk('public')->assertExists($media->path);
    Storage::disk('public')->assertExists($media->thumbnail_path);
    expect($media->path)->toEndWith('.webp')
        ->and($media->thumbnail_path)->toEndWith('.webp')
        ->and($media->mime_type)->toBe('image/webp')
        ->and($media->width)->toBe(120)
        ->and($media->height)->toBe(80)
        ->and($media->size)->toBeGreaterThan(0);
});

test('keeps an already optimised image untouched but still scales a thumbnail', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $manager = new ImageManager(new Driver());
    $content = (string) $manager->createImage(1200, 800)->fill('red')->encode(new JpegEncoder());
    $file = UploadedFile::fake()->createWithContent('image.jpg', $content);

    $media = (new StoreMediaAction())->handle($file, preserveFormat: true);

    expect($media->path)->toEndWith('.jpg')
        ->and($media->mime_type)->toBe('image/jpeg')
        ->and($media->width)->toBe(1200)
        ->and($media->height)->toBe(800)
        ->and($media->thumbnail_path)->toEndWith('.webp')
        ->and(Storage::disk('public')->get($media->path))->toBe($content);

    Storage::disk('public')->assertExists($media->thumbnail_path);

    expect(mb_strlen(Storage::disk('public')->get($media->thumbnail_path), '8bit'))
        ->toBeLessThan(mb_strlen($content, '8bit'));
});

test('scales the thumbnail down so its shorter side is 640 pixels', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $manager = new ImageManager(new Driver());
    $file = UploadedFile::fake()->createWithContent(
        'image.jpg',
        (string) $manager->createImage(1400, 1600)->fill('red')->encode(new JpegEncoder()),
    );

    $media = (new StoreMediaAction())->handle($file);

    $thumbnail = $manager->decode(Storage::disk('public')->get($media->thumbnail_path));

    expect($thumbnail->width())->toBe(640)
        ->and($thumbnail->height())->toBe(731);
});

test('also stores a small derivative scaled to 160 pixels', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $manager = new ImageManager(new Driver());
    $file = UploadedFile::fake()->createWithContent(
        'image.jpg',
        (string) $manager->createImage(1400, 1600)->fill('red')->encode(new JpegEncoder()),
    );

    $media = (new StoreMediaAction())->handle($file);

    Storage::disk('public')->assertExists($media->small_thumbnail_path);

    $small = $manager->decode(Storage::disk('public')->get($media->small_thumbnail_path));

    expect($media->small_thumbnail_path)->toStartWith('thumbnails/small-')
        ->and($small->width())->toBe(160)
        ->and($small->height())->toBe(183)
        ->and(mb_strlen(Storage::disk('public')->get($media->small_thumbnail_path), '8bit'))
        ->toBeLessThan(mb_strlen(Storage::disk('public')->get($media->thumbnail_path), '8bit'));
});

test('leaves the small derivative empty when generateThumbnail is false', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $manager = new ImageManager(new Driver());
    $file = UploadedFile::fake()->createWithContent(
        'image.jpg',
        (string) $manager->createImage(400, 400)->fill('red')->encode(new JpegEncoder()),
    );

    $media = (new StoreMediaAction())->handle($file, generateThumbnail: false);

    expect($media->thumbnail_path)->toBeNull()
        ->and($media->small_thumbnail_path)->toBeNull();
});

test('does not upscale a thumbnail smaller than the target size', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $manager = new ImageManager(new Driver());
    $file = UploadedFile::fake()->createWithContent(
        'image.jpg',
        (string) $manager->createImage(200, 120)->fill('red')->encode(new JpegEncoder()),
    );

    $media = (new StoreMediaAction())->handle($file);

    $thumbnail = $manager->decode(Storage::disk('public')->get($media->thumbnail_path));

    expect($thumbnail->width())->toBe(200)
        ->and($thumbnail->height())->toBe(120);
});

test('stores string content as raw when image extension is not loaded', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', false);

    $manager = new ImageManager(new Driver());
    $imageContent = (string) $manager->createImage(100, 100)->fill('red')->encode(new JpegEncoder());

    $media = (new StoreMediaAction())->handle($imageContent);

    Storage::disk('public')->assertExists($media->path);
    expect($media->path)->toEndWith('.jpg')
        ->and($media->thumbnail_path)->toBeNull();
});

test('processes string content to webp when image extension is loaded', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $manager = new ImageManager(new Driver());
    $imageContent = (string) $manager->createImage(100, 100)->fill('red')->encode(new JpegEncoder());

    $media = (new StoreMediaAction())->handle($imageContent);

    Storage::disk('public')->assertExists($media->path);
    Storage::disk('public')->assertExists($media->thumbnail_path);
    expect($media->path)->toEndWith('.webp')
        ->and($media->thumbnail_path)->toEndWith('.webp');
});

test('generates unique filename for each storage operation', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $manager = new ImageManager(new Driver());
    $file1 = UploadedFile::fake()->createWithContent('image1.jpg', (string) $manager->createImage(100, 100)->fill('red')->encode(new JpegEncoder()));
    $file2 = UploadedFile::fake()->createWithContent('image2.jpg', (string) $manager->createImage(100, 100)->fill('blue')->encode(new JpegEncoder()));
    $action = new StoreMediaAction();

    expect($action->handle($file1)->path)->not->toBe($action->handle($file2)->path);
});

test('skips thumbnail generation when generateThumbnail is false', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $manager = new ImageManager(new Driver());
    $file = UploadedFile::fake()->createWithContent('image.jpg', (string) $manager->createImage(100, 100)->fill('red')->encode(new JpegEncoder()));

    $media = (new StoreMediaAction())->handle($file, generateThumbnail: false);

    Storage::disk('public')->assertExists($media->path);
    expect($media->path)->toStartWith('media/')
        ->and($media->thumbnail_path)->toBeNull();
});

test('stores an uploaded video without image processing', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $file = UploadedFile::fake()->create('clip.mp4', 2000, 'video/mp4');

    $media = (new StoreMediaAction())->handle($file);

    Storage::disk('public')->assertExists($media->path);
    expect($media->type)->toBe(MediaType::Video)
        ->and($media->disk)->toBe('public')
        ->and($media->path)->toStartWith('videos/')
        ->and($media->thumbnail_path)->toBeNull()
        ->and($media->mime_type)->toBe('video/mp4')
        ->and($media->width)->toBeNull()
        ->and($media->height)->toBeNull()
        ->and($media->original_filename)->toBe('clip.mp4');
});

test('stores a non-image non-video upload as a file', function () {
    Storage::fake('public');
    Config::set('images.extension_loaded', true);

    $file = UploadedFile::fake()->create('spec.pdf', 500, 'application/pdf');

    $media = (new StoreMediaAction())->handle($file);

    Storage::disk('public')->assertExists($media->path);
    expect($media->type)->toBe(MediaType::File)
        ->and($media->path)->toStartWith('files/')
        ->and($media->thumbnail_path)->toBeNull()
        ->and($media->mime_type)->toBe('application/pdf');
});
