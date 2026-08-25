<?php

declare(strict_types=1);

use App\Enums\DisplayTaxTotals;
use App\Enums\SettingGroup;
use App\Enums\SettingType;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Utilities\InvoicePdfGenerator;
use Illuminate\Support\Facades\Storage;

covers(InvoicePdfGenerator::class);

uses()->group('utilities', 'invoice');

beforeEach(function () {
    Setting::query()->updateOrCreate(
        ['key' => 'display_tax_totals'],
        [
            'value' => DisplayTaxTotals::Single->value,
            'group' => SettingGroup::Tax,
            'type' => SettingType::Text,
        ]
    );

    Cache::flush();
});

test('generates valid pdf output', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $output = InvoicePdfGenerator::generate($order);

    expect($output)->toBeString()
        ->and($output)->toStartWith('%PDF');
});

test('generates valid pdf output when store logo is stored locally', function () {
    Storage::fake('public');
    Storage::disk('public')->put('images/logo.png', 'fake-logo-bytes');

    $media = Media::factory()->uploaded()->create([
        'disk' => 'public',
        'path' => 'images/logo.png',
    ]);

    Setting::query()->updateOrCreate(
        ['key' => 'store_logo'],
        [
            'value' => (string) $media->id,
            'group' => SettingGroup::Store,
            'type' => SettingType::Asset,
        ]
    );

    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $output = InvoicePdfGenerator::generate($order);

    expect($output)->toBeString()
        ->and($output)->toStartWith('%PDF');
});

test('generates valid pdf output when store logo is external-only', function () {
    $media = Media::factory()->create([
        'disk' => 'public',
        'path' => null,
        'external_url' => 'https://example.com/logo.png',
    ]);

    Setting::query()->updateOrCreate(
        ['key' => 'store_logo'],
        [
            'value' => (string) $media->id,
            'group' => SettingGroup::Store,
            'type' => SettingType::Asset,
        ]
    );

    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $output = InvoicePdfGenerator::generate($order);

    expect($output)->toBeString()
        ->and($output)->toStartWith('%PDF');
});

test('eager loads required relationships', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    expect($order->relationLoaded('items'))->toBeFalse();

    InvoicePdfGenerator::generate($order);

    expect($order->relationLoaded('items'))->toBeTrue()
        ->and($order->relationLoaded('billingAddress'))->toBeTrue()
        ->and($order->relationLoaded('shippingAddress'))->toBeTrue()
        ->and($order->relationLoaded('taxDetails'))->toBeTrue();
});

test('does not reload already loaded relationships', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);
    $order->load('items');

    $itemsBeforeGenerate = $order->items;

    InvoicePdfGenerator::generate($order);

    expect($order->items)->toBe($itemsBeforeGenerate);
});
