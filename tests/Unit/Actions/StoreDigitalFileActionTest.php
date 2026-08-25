<?php

declare(strict_types=1);

use App\Actions\StoreDigitalFileAction;
use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

covers(StoreDigitalFileAction::class);

uses()->group('actions', 'product');

test('stores an uploaded file under the downloads directory and returns its metadata', function () {
    Storage::fake();

    $file = UploadedFile::fake()->create('manual.zip', 100, 'application/zip');

    $media = app(StoreDigitalFileAction::class)->handle($file);

    expect($media)->toBeInstanceOf(Media::class)
        ->and($media->type)->toBe(MediaType::File)
        ->and($media->path)->toStartWith('downloads/')
        ->and($media->path)->toEndWith('.zip')
        ->and($media->original_filename)->toBe('manual.zip')
        ->and($media->size)->toBe($file->getSize())
        ->and($media->mime_type)->toBe('application/zip');

    Storage::disk()->assertExists($media->path);
});

test('generates a random storage filename that does not leak the original name', function () {
    Storage::fake();
    Str::createRandomStringsNormally();

    $file = UploadedFile::fake()->create('Secret Document.pdf', 10, 'application/pdf');

    $media = app(StoreDigitalFileAction::class)->handle($file);

    expect($media->path)->not->toContain('Secret Document')
        ->and($media->path)->toMatch('/^downloads\/[A-Za-z0-9]{40}\.pdf$/')
        ->and($media->original_filename)->toBe('Secret Document.pdf');
});
