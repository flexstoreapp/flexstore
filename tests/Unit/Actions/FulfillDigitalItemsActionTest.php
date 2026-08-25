<?php

declare(strict_types=1);

use App\Actions\FulfillDigitalItemsAction;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderItem;
use App\Models\OrderItemDownload;
use App\Models\Product;
use App\Models\ProductDownload;

covers(FulfillDigitalItemsAction::class);

uses()->group('actions', 'downloads');

function fulfillDigitalItems(Order $order): void
{
    app(FulfillDigitalItemsAction::class)->handle($order);
}

test('only grants variant-scoped files matching the item variant', function () {
    $product = Product::factory()->digital()->withVariants()->create();
    $variant = $product->variants()->first();
    $otherVariant = $product->variants()->where('id', '!=', $variant->id)->first();

    $productLevel = ProductDownload::factory()->create(['product_id' => $product->id]);
    $matching = ProductDownload::factory()->forVariant($variant)->create();
    ProductDownload::factory()->forVariant($otherVariant)->create();

    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid]);
    $item = OrderItem::factory()->forOrder($order)->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'requires_shipping' => false,
    ]);

    fulfillDigitalItems($order);

    $grantedIds = OrderItemDownload::query()
        ->where('order_item_id', $item->id)
        ->pluck('product_download_id')
        ->all();

    expect($grantedIds)->toHaveCount(2)
        ->toContain($productLevel->id)
        ->toContain($matching->id);
});

test('is idempotent and does not duplicate grants on a second call', function () {
    $product = Product::factory()->digital()->create();
    ProductDownload::factory()->count(2)->create(['product_id' => $product->id]);

    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid]);
    $item = OrderItem::factory()->forOrder($order)->create([
        'product_id' => $product->id,
        'requires_shipping' => false,
    ]);

    fulfillDigitalItems($order);
    fulfillDigitalItems($order->refresh());

    expect(OrderItemDownload::query()->where('order_item_id', $item->id)->count())->toBe(2);
});

test('auto-fulfills a digital-only order and records an activity', function () {
    $product = Product::factory()->digital()->create();
    ProductDownload::factory()->create(['product_id' => $product->id]);

    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);
    OrderItem::factory()->forOrder($order)->create([
        'product_id' => $product->id,
        'requires_shipping' => false,
    ]);

    fulfillDigitalItems($order);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and(OrderActivity::query()
            ->where('order_id', $order->id)
            ->where('type', OrderActivityType::ItemsFulfilled)
            ->exists())->toBeTrue();
});

test('does not auto-fulfill a mixed order containing a physical item', function () {
    $digital = Product::factory()->digital()->create();
    ProductDownload::factory()->create(['product_id' => $digital->id]);
    $physical = Product::factory()->create();

    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);
    OrderItem::factory()->forOrder($order)->create([
        'product_id' => $digital->id,
        'requires_shipping' => false,
    ]);
    OrderItem::factory()->forOrder($order)->create([
        'product_id' => $physical->id,
        'requires_shipping' => true,
    ]);

    fulfillDigitalItems($order);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and(OrderActivity::query()
            ->where('order_id', $order->id)
            ->where('type', OrderActivityType::ItemsFulfilled)
            ->exists())->toBeFalse()
        ->and(OrderItemDownload::query()->where('order_id', $order->id)->count())->toBe(1);
});

test('does not auto-fulfill a digital-only order when no applicable download exists', function () {
    // A digital product with no files at all (e.g. CSV-imported, or files removed).
    $product = Product::factory()->digital()->create();

    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);
    OrderItem::factory()->forOrder($order)->create([
        'product_id' => $product->id,
        'requires_shipping' => false,
    ]);

    fulfillDigitalItems($order);

    expect(OrderItemDownload::query()->where('order_id', $order->id)->count())->toBe(0)
        ->and($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
});

test('creates no grants when the order is not paid', function () {
    $product = Product::factory()->digital()->create();
    ProductDownload::factory()->create(['product_id' => $product->id]);

    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Unpaid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);
    OrderItem::factory()->forOrder($order)->create([
        'product_id' => $product->id,
        'requires_shipping' => false,
    ]);

    fulfillDigitalItems($order);

    expect(OrderItemDownload::query()->where('order_id', $order->id)->count())->toBe(0)
        ->and($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
});
