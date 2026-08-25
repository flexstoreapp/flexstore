<?php

declare(strict_types=1);

use App\Actions\UpdateBrandAction;
use App\DTOs\UpdateBrandInput;
use App\Models\Brand;
use App\Models\Media;

covers(UpdateBrandAction::class, UpdateBrandInput::class);

uses()->group('actions', 'brand');

test('updates a brand with provided fields', function () {
    $brand = Brand::factory()->create();
    $media = Media::factory()->create();

    $data = [
        'name' => 'Updated Brand',
        'url_handle' => 'updated-brand',
        'description' => 'Updated description',
        'image_id' => $media->id,
        'is_active' => false,
    ];

    $action = app(UpdateBrandAction::class);
    $result = $action->handle($brand, UpdateBrandInput::fromArray($data));

    expect($result)->toBeInstanceOf(Brand::class)
        ->and($result->name)->toBe('Updated Brand')
        ->and($result->url_handle)->toBe('updated-brand')
        ->and($result->description)->toBe('Updated description')
        ->and($result->image_id)->toBe($media->id)
        ->and($result->is_active)->toBeFalse();

    $brand->refresh();
    expect($brand->name)->toBe('Updated Brand')
        ->and($brand->image_id)->toBe($media->id)
        ->and($brand->is_active)->toBeFalse();
});

test('updates a brand with only specified fields', function () {
    $media = Media::factory()->create();
    $brand = Brand::factory()->create(['image_id' => $media->id]);

    $data = [
        'name' => 'Updated Brand',
        'url_handle' => 'updated-brand',
    ];

    $action = app(UpdateBrandAction::class);
    $result = $action->handle($brand, UpdateBrandInput::fromArray($data));

    expect($result->name)->toBe('Updated Brand')
        ->and($result->url_handle)->toBe('updated-brand')
        ->and($result->description)->toBe($brand->description)
        ->and($result->image_id)->toBe($media->id)
        ->and($result->is_active)->toBe($brand->is_active);

    $brand->refresh();
    expect($brand->name)->toBe('Updated Brand')
        ->and($brand->image_id)->toBe($media->id);
});

test('updates a brand with translatable fields', function () {
    $brand = Brand::factory()->create();

    $data = [
        'name' => castAsTranslatableArray('Updated Translated Brand'),
        'description' => castAsTranslatableArray('Updated translated description'),
    ];

    $action = app(UpdateBrandAction::class);
    $result = $action->handle($brand, UpdateBrandInput::fromArray($data));

    expect($result->name)->toBe('Updated Translated Brand')
        ->and($result->description)->toBe('Updated translated description');

    $brand->refresh();
    expect($brand->name)->toBe('Updated Translated Brand');
});
