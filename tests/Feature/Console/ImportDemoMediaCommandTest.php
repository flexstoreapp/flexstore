<?php

declare(strict_types=1);

use App\Console\Commands\ImportDemoMediaCommand;
use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

covers(ImportDemoMediaCommand::class);

uses()->group('console', 'media');

function demoMediaSource(string $relativePath): string
{
    $source = sys_get_temp_dir() . '/demo-media-test-' . mb_substr(md5($relativePath), 0, 8);
    $absolutePath = $source . '/' . $relativePath;

    File::ensureDirectoryExists(dirname($absolutePath));
    File::put($absolutePath, UploadedFile::fake()->image('source.jpg', 800, 600)->getContent());

    return $source;
}

test('placeholder media rows are replaced with pipeline-stored files', function () {
    Storage::fake('public');

    $source = demoMediaSource('assets/products/demo/demo-1.jpg');

    $media = Media::query()->create([
        'type' => MediaType::Image,
        'disk' => 'public',
        'path' => 'assets/products/demo/demo-1.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    artisan('demo:import-media', ['--source' => $source])->assertSuccessful();

    $media->refresh();

    expect($media->path)->toStartWith('media/')
        ->and($media->thumbnail_path)->toStartWith('thumbnails/')
        ->and($media->original_filename)->toBe('demo-1.jpg')
        ->and($media->size)->toBeGreaterThan(0)
        ->and($media->width)->toBe(800)
        ->and($media->height)->toBe(600)
        ->and(Media::query()->count())->toBe(1);

    Storage::disk('public')->assertExists($media->path);
    Storage::disk('public')->assertExists($media->thumbnail_path);

    File::deleteDirectory($source);
});

test('building the cache writes a manifest and the generated thumbnails', function () {
    Storage::fake('public');

    $source = demoMediaSource('assets/products/demo/built.jpg');

    Media::query()->create([
        'type' => MediaType::Image,
        'disk' => 'public',
        'path' => 'assets/products/demo/built.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    artisan('demo:import-media', ['--source' => $source, '--build' => true])->assertSuccessful();

    $manifest = json_decode(File::get($source . '/cache/manifest.json'), true);

    expect($manifest)->toHaveKey('assets/products/demo/built.jpg');

    $entry = $manifest['assets/products/demo/built.jpg'];

    expect($entry['path'])->toStartWith('media/')
        ->and($entry['width'])->toBe(800)
        ->and($entry['height'])->toBe(600)
        ->and($entry['original_filename'])->toBe('built.jpg');

    expect(File::exists($source . '/cache/' . $entry['thumbnail_path']))->toBeTrue();

    File::deleteDirectory($source);
});

test('rebuilding the cache produces the same paths and leaves no stale thumbnails', function () {
    Storage::fake('public');

    $source = demoMediaSource('assets/products/demo/stable.jpg');
    $relativePath = 'assets/products/demo/stable.jpg';

    $media = Media::query()->create([
        'type' => MediaType::Image,
        'disk' => 'public',
        'path' => $relativePath,
        'mime_type' => 'image/jpeg',
    ]);

    artisan('demo:import-media', ['--source' => $source, '--build' => true])->assertSuccessful();

    $first = File::get($source . '/cache/manifest.json');
    $thumbnails = File::files($source . '/cache/thumbnails');

    $media->forceFill(['path' => $relativePath])->save();
    Storage::fake('public');

    artisan('demo:import-media', ['--source' => $source, '--build' => true])->assertSuccessful();

    expect(File::get($source . '/cache/manifest.json'))->toBe($first)
        ->and(File::files($source . '/cache/thumbnails'))->toHaveCount(count($thumbnails));

    File::deleteDirectory($source);
});

test('a cached manifest installs media without touching the image pipeline', function () {
    Storage::fake('public');

    $source = demoMediaSource('assets/products/demo/cached.jpg');

    $media = Media::query()->create([
        'type' => MediaType::Image,
        'disk' => 'public',
        'path' => 'assets/products/demo/cached.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    artisan('demo:import-media', ['--source' => $source, '--build' => true])->assertSuccessful();

    $built = $media->refresh()->only(['path', 'thumbnail_path', 'size', 'width', 'height']);

    $media->forceFill(['path' => 'assets/products/demo/cached.jpg'])->save();
    Storage::fake('public');

    artisan('demo:import-media', ['--source' => $source])
        ->expectsOutputToContain('Copied 1 media file(s) from the processed cache.')
        ->expectsOutputToContain('Imported 0 media file(s) through the pipeline.')
        ->assertSuccessful();

    expect($media->refresh()->only(['path', 'thumbnail_path', 'size', 'width', 'height']))->toBe($built);

    Storage::disk('public')->assertExists($built['path']);
    Storage::disk('public')->assertExists($built['thumbnail_path']);

    File::deleteDirectory($source);
});

test('a manifest entry with a missing thumbnail falls back to the pipeline', function () {
    Storage::fake('public');

    $source = demoMediaSource('assets/products/demo/stale.jpg');

    $media = Media::query()->create([
        'type' => MediaType::Image,
        'disk' => 'public',
        'path' => 'assets/products/demo/stale.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    artisan('demo:import-media', ['--source' => $source, '--build' => true])->assertSuccessful();

    File::deleteDirectory($source . '/cache/thumbnails');

    $media->refresh()->forceFill(['path' => 'assets/products/demo/stale.jpg'])->save();

    artisan('demo:import-media', ['--source' => $source])
        ->expectsOutputToContain('Copied 0 media file(s) from the processed cache.')
        ->expectsOutputToContain('Imported 1 media file(s) through the pipeline.')
        ->assertSuccessful();

    expect($media->refresh()->thumbnail_path)->toStartWith('thumbnails/');

    Storage::disk('public')->assertExists($media->thumbnail_path);

    File::deleteDirectory($source);
});

test('svg logos are stored without being rasterised', function () {
    Storage::fake('public');

    $source = sys_get_temp_dir() . '/demo-media-test-svg';
    $relativePath = 'assets/logo/light.svg';

    File::ensureDirectoryExists(dirname($source . '/' . $relativePath));
    File::put($source . '/' . $relativePath, File::get(resource_path('demo/assets/logo/light.svg')));

    $media = Media::query()->create([
        'type' => MediaType::Image,
        'disk' => 'public',
        'path' => $relativePath,
        'mime_type' => 'image/svg+xml',
    ]);

    artisan('demo:import-media', ['--source' => $source])->assertSuccessful();

    $media->refresh();

    expect($media->path)->toStartWith('media/')
        ->and($media->path)->toEndWith('.svg')
        ->and($media->mime_type)->toBe('image/svg+xml')
        ->and($media->original_filename)->toBe('light.svg');

    expect(Storage::disk('public')->get($media->path))->toContain('<svg');

    File::deleteDirectory($source);
});

test('missing source files are reported without failing the command', function () {
    Storage::fake('public');

    $source = demoMediaSource('assets/products/demo/present.jpg');

    Media::query()->create([
        'type' => MediaType::Image,
        'disk' => 'public',
        'path' => 'assets/products/demo/absent.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    artisan('demo:import-media', ['--source' => $source])
        ->expectsOutputToContain('Missing source file: assets/products/demo/absent.jpg')
        ->assertSuccessful();

    File::deleteDirectory($source);
});

test('media rows already stored in the media directory are left untouched', function () {
    Storage::fake('public');

    $source = demoMediaSource('assets/products/demo/untouched.jpg');

    $media = Media::query()->create([
        'type' => MediaType::Image,
        'disk' => 'public',
        'path' => 'media/already-imported.webp',
        'mime_type' => 'image/webp',
    ]);

    artisan('demo:import-media', ['--source' => $source])
        ->expectsOutputToContain('No demo media to import.')
        ->assertSuccessful();

    expect($media->refresh()->path)->toBe('media/already-imported.webp');

    File::deleteDirectory($source);
});

test('a missing source directory fails the command', function () {
    artisan('demo:import-media', ['--source' => sys_get_temp_dir() . '/demo-media-does-not-exist'])
        ->assertFailed();
});

test('files no media row references are pruned', function () {
    Storage::fake('public');

    $source = demoMediaSource('assets/products/demo/prune-1.jpg');

    Media::query()->create([
        'type' => MediaType::Image,
        'disk' => 'public',
        'path' => 'assets/products/demo/prune-1.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    Storage::disk('public')->put('media/leftover.webp', 'stale');
    Storage::disk('public')->put('thumbnails/leftover.webp', 'stale');

    artisan(ImportDemoMediaCommand::class, ['--source' => $source, '--prune' => true])->assertSuccessful();

    Storage::disk('public')->assertMissing('media/leftover.webp');
    Storage::disk('public')->assertMissing('thumbnails/leftover.webp');

    $imported = Media::query()->first();
    expect($imported->path)->not->toBeNull();
    Storage::disk('public')->assertExists($imported->path);
    Storage::disk('public')->assertExists($imported->thumbnail_path);
});

test('nothing is pruned unless --prune is passed', function () {
    Storage::fake('public');

    $source = demoMediaSource('assets/products/demo/keep-1.jpg');

    Media::query()->create([
        'type' => MediaType::Image,
        'disk' => 'public',
        'path' => 'assets/products/demo/keep-1.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    Storage::disk('public')->put('media/leftover.webp', 'stale');

    artisan(ImportDemoMediaCommand::class, ['--source' => $source])->assertSuccessful();

    Storage::disk('public')->assertExists('media/leftover.webp');
});

test('the no-op path does not prune unless asked', function () {
    Storage::fake('public');

    Storage::disk('public')->put('media/leftover.webp', 'stale');

    artisan(ImportDemoMediaCommand::class, ['--source' => sys_get_temp_dir()])->assertSuccessful();

    Storage::disk('public')->assertExists('media/leftover.webp');
});

test('the no-op path prunes when explicitly asked', function () {
    Storage::fake('public');

    Storage::disk('public')->put('media/leftover.webp', 'stale');

    artisan(ImportDemoMediaCommand::class, ['--source' => sys_get_temp_dir(), '--prune' => true])->assertSuccessful();

    Storage::disk('public')->assertMissing('media/leftover.webp');
});
