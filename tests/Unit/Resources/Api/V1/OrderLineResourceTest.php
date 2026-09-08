<?php

declare(strict_types=1);

use App\Http\Resources\Api\V1\OrderLineResource;
use App\Models\Media;
use Illuminate\Http\Request;

covers(OrderLineResource::class);

uses()->group('resources', 'api');

test('a line built from a media model exposes the media and no thumbnail url', function (): void {
    $media = Media::factory()->make(['id' => 42]);

    $line = (new OrderLineResource([
        'product_title' => ['en' => 'Merino crew knit', 'ar' => 'كنزة'],
        'variant_title' => ['en' => 'Oatmeal / M'],
        'quantity' => 2,
        'unit_price' => '49.9900',
        'total_price' => '99.9800',
        'featured_media' => $media,
    ]))->toArray(Request::create('/'));

    expect($line['featured_media'])->not->toBeNull()
        ->and($line['thumbnail_url'])->toBeNull()
        ->and($line['product_title'])->toBe('Merino crew knit')
        ->and($line['variant_title'])->toBe('Oatmeal / M');
});

test('a checkout session snapshot exposes its thumbnail url and no media', function (): void {
    $line = (new OrderLineResource([
        'product_title' => ['en' => 'Merino crew knit'],
        'quantity' => 1,
        'thumbnail_url' => 'https://your-store.com/storage/products/merino-100.jpg',
    ]))->toArray(Request::create('/'));

    expect($line['thumbnail_url'])->toBe('https://your-store.com/storage/products/merino-100.jpg')
        ->and($line['featured_media'])->toBeNull();
});

test('a line with no image leaves both image fields null', function (): void {
    $line = (new OrderLineResource([
        'product_title' => ['en' => 'Merino crew knit'],
        'quantity' => 1,
    ]))->toArray(Request::create('/'));

    expect($line['featured_media'])->toBeNull()
        ->and($line['thumbnail_url'])->toBeNull()
        ->and($line['variant_title'])->toBeNull()
        ->and($line['unit_price'])->toBeNull();
});

test('titles fall back to the store locale when the active one is missing', function (): void {
    app()->setLocale('ar');

    $line = (new OrderLineResource([
        'product_title' => ['en' => 'Merino crew knit'],
        'quantity' => 1,
    ]))->toArray(Request::create('/'));

    expect($line['product_title'])->toBe('Merino crew knit');
});
