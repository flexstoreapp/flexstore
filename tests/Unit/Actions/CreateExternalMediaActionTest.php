<?php

declare(strict_types=1);

use App\Actions\CreateExternalMediaAction;
use App\Enums\MediaType;
use App\Models\Media;

covers(CreateExternalMediaAction::class);

uses()->group('actions', 'media');

test('creates an image on the public disk by default', function () {
    $media = app(CreateExternalMediaAction::class)->handle('https://cdn.example.test/shoe.jpg');

    expect($media->external_url)->toBe('https://cdn.example.test/shoe.jpg')
        ->and($media->type)->toBe(MediaType::Image)
        ->and($media->disk)->toBe('public');
});

test('creates media of the requested type', function () {
    $media = app(CreateExternalMediaAction::class)->handle('https://cdn.example.test/clip.mp4', MediaType::Video);

    expect($media->type)->toBe(MediaType::Video);
});

test('reuses the existing media for a url that was already imported', function () {
    $action = app(CreateExternalMediaAction::class);

    $first = $action->handle('https://cdn.example.test/shoe.jpg');
    $second = $action->handle('https://cdn.example.test/shoe.jpg');

    expect($second->id)->toBe($first->id)
        ->and(Media::query()->where('external_url', 'https://cdn.example.test/shoe.jpg')->count())->toBe(1);
});
