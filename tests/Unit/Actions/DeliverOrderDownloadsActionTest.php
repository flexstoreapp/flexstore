<?php

declare(strict_types=1);

use App\Actions\DeliverOrderDownloadsAction;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemDownload;
use App\Models\Product;
use App\Models\ProductDownload;
use App\Models\Setting;
use App\Notifications\CustomerDigitalDownloadsReadyNotification;
use Illuminate\Support\Facades\Notification;

covers(DeliverOrderDownloadsAction::class);

uses()->group('actions', 'downloads');

function deliverDownloads(Order $order): void
{
    app(DeliverOrderDownloadsAction::class)->handle($order);
}

test('fulfills digital items and notifies the customer when an order is paid', function () {
    Notification::fake();
    Setting::setValue('notification_customer_order_confirmed', true);

    $product = Product::factory()->digital()->create();
    ProductDownload::factory()->create(['product_id' => $product->id]);

    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid, 'customer_id' => null]);
    OrderItem::factory()->forOrder($order)->create([
        'product_id' => $product->id,
        'requires_shipping' => false,
    ]);

    deliverDownloads($order);

    expect(OrderItemDownload::query()->where('order_id', $order->id)->exists())->toBeTrue();
    Notification::assertSentOnDemand(CustomerDigitalDownloadsReadyNotification::class);
});

test('does nothing when the order is not paid', function () {
    Notification::fake();
    Setting::setValue('notification_customer_order_confirmed', true);

    $product = Product::factory()->digital()->create();
    ProductDownload::factory()->create(['product_id' => $product->id]);

    $order = Order::factory()->create(['payment_status' => PaymentStatus::Unpaid, 'customer_id' => null]);
    OrderItem::factory()->forOrder($order)->create([
        'product_id' => $product->id,
        'requires_shipping' => false,
    ]);

    deliverDownloads($order);

    expect(OrderItemDownload::query()->where('order_id', $order->id)->exists())->toBeFalse();
    Notification::assertNothingSent();
});

test('does not notify a paid order with no digital items', function () {
    Notification::fake();
    Setting::setValue('notification_customer_order_confirmed', true);

    $product = Product::factory()->create();

    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid, 'customer_id' => null]);
    OrderItem::factory()->forOrder($order)->create([
        'product_id' => $product->id,
        'requires_shipping' => true,
    ]);

    deliverDownloads($order);

    Notification::assertNothingSent();
});
