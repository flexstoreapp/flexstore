<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariant;
use App\Notifications\AdminLowStockNotification;
use Illuminate\Notifications\Messages\MailMessage;

covers(AdminLowStockNotification::class);

uses()->group('notifications');

test('uses mail channel', function () {
    $product = Product::factory()->create();
    $notification = new AdminLowStockNotification($product);

    expect($notification->via($product))->toBe(['mail']);
});

test('mail contains product title for base product', function () {
    $product = Product::factory()->create([
        'title' => 'Test Widget',
        'track_stock' => true,
        'stock' => 3,
    ]);
    $notification = new AdminLowStockNotification($product);

    $mail = $notification->toMail($product);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain('Test Widget')
        ->and($mail->render())->toContain('3');
});

test('mail contains product and variant title for variant', function () {
    $product = Product::factory()->create(['title' => 'T-Shirt']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'title' => 'Large / Red',
        'track_stock' => true,
        'stock' => 2,
    ]);
    $notification = new AdminLowStockNotification($product, $variant);

    $mail = $notification->toMail($product);

    expect($mail->subject)->toContain('T-Shirt - Large / Red')
        ->and($mail->render())->toContain('2');
});

test('mail contains link to inventory page', function () {
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 1]);
    $notification = new AdminLowStockNotification($product);

    $rendered = $notification->toMail($product)->render();

    expect($rendered)->toContain(route('admin.inventory.index'));
});
