<?php

declare(strict_types=1);

use App\Actions\StoreBrandAction;
use App\DTOs\StoreBrandInput;
use App\Models\Brand;
use App\Models\Media;

covers(StoreBrandAction::class, StoreBrandInput::class);

uses()->group('actions', 'brand');

test('creates a brand with required fields', function () {
    $media = Media::factory()->create();

    $data = [
        'name' => 'Test Brand',
        'url_handle' => 'test-brand',
        'description' => 'Test description',
        'image_id' => $media->id,
        'is_active' => true,
    ];

    $action = app(StoreBrandAction::class);
    $result = $action->handle(StoreBrandInput::fromArray($data));

    expect($result)->toBeInstanceOf(Brand::class)
        ->and($result->name)->toBe('Test Brand')
        ->and($result->url_handle)->toBe('test-brand')
        ->and($result->description)->toBe('Test description')
        ->and($result->image_id)->toBe($media->id)
        ->and($result->is_active)->toBeTrue()
        ->and(Brand::query()->count())->toBe(1);
});

test('creates a brand without description and image', function () {
    $data = [
        'name' => 'Brand Name',
        'url_handle' => 'brand-name',
        'is_active' => false,
    ];

    $action = app(StoreBrandAction::class);
    $result = $action->handle(StoreBrandInput::fromArray($data));

    expect($result->name)->toBe('Brand Name')
        ->and($result->url_handle)->toBe('brand-name')
        ->and($result->image_id)->toBeNull()
        ->and($result->is_active)->toBeFalse();
});

test('creates a brand with translatable fields', function () {
    $data = [
        'name' => castAsTranslatableArray('Translated Brand'),
        'url_handle' => 'translated-brand',
        'description' => castAsTranslatableArray('Translated description'),
        'is_active' => true,
    ];

    $action = app(StoreBrandAction::class);
    $result = $action->handle(StoreBrandInput::fromArray($data));

    expect($result->name)->toBe('Translated Brand')
        ->and($result->description)->toBe('Translated description');
});
