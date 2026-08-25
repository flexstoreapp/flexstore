<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItemDownload;
use Illuminate\Database\QueryException;

covers(OrderItemDownload::class);

uses()->group('models', 'downloads');

test('isRevoked is true when the order is canceled', function () {
    $order = Order::factory()->create(['canceled_at' => now()]);

    expect(OrderItemDownload::factory()->create(['order_id' => $order->id])->isRevoked())->toBeTrue();
});

test('isRevoked is true when the order is fully refunded', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Refunded]);

    expect(OrderItemDownload::factory()->create(['order_id' => $order->id])->isRevoked())->toBeTrue();
});

test('isRevoked is false for a paid, uncanceled order', function () {
    $order = Order::factory()->paid()->create();

    expect(OrderItemDownload::factory()->create(['order_id' => $order->id])->isRevoked())->toBeFalse();
});

test('enforces a single grant per order item and product download', function () {
    $first = OrderItemDownload::factory()->create();

    expect(fn () => OrderItemDownload::factory()->create([
        'order_item_id' => $first->order_item_id,
        'product_download_id' => $first->product_download_id,
    ]))->toThrow(QueryException::class);
});
