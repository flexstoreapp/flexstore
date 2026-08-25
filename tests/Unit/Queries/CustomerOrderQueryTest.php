<?php

declare(strict_types=1);

use App\Actions\SyncMediaAction;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OrderRefundItem;
use App\Models\OrderTaxDetail;
use App\Models\Product;
use App\Models\User;
use App\Queries\CustomerOrderQuery;

covers(CustomerOrderQuery::class);

uses()->group('queries', 'account');

test('returns order with loaded relations', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderItem::factory()->forOrder($order)->create();
    OrderAddress::factory()->billing()->forOrder($order)->create();
    OrderAddress::factory()->shipping()->forOrder($order)->create();
    OrderTaxDetail::factory()->forOrder($order)->create();

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    expect($result)->toBeInstanceOf(Order::class)
        ->and($result->relationLoaded('items'))->toBeTrue()
        ->and($result->relationLoaded('billingAddress'))->toBeTrue()
        ->and($result->relationLoaded('shippingAddress'))->toBeTrue()
        ->and($result->relationLoaded('taxDetails'))->toBeTrue();
});

test('resolves the address state to its display name', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'US',
        'state' => 'CA',
    ]);

    $result = app(CustomerOrderQuery::class)->execute($order->id, $user);

    expect($result->shippingAddress->state)->toBe('California')
        ->and($result->shippingAddress->toArray())->not->toHaveKey('state_name');
});

test('appends thumbnail to order item and hides variant relation', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderItem::factory()->forOrder($order)->forProduct($product)->create();

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    $item = $result->items->first();

    expect($item->relationLoaded('media'))->toBeTrue()
        ->and($item->relationLoaded('productVariant'))->toBeFalse()
        ->and($item->relationLoaded('product'))->toBeTrue();
});

test('exposes product URL handle and is_active but hides media', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'url_handle' => 'test-product',
        'is_active' => true,
    ]);
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderItem::factory()->forOrder($order)->forProduct($product)->create();

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    $item = $result->items->first();

    expect($item->product)->not->toBeNull()
        ->and($item->product->url_handle)->toBe('test-product')
        ->and($item->product->is_active)->toBeTrue()
        ->and($item->product->relationLoaded('media'))->toBeFalse();
});

test('returns product thumbnail when no variant exists', function () {
    $user = User::factory()->create();
    $media = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$media->id]);
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderItem::factory()->forOrder($order)->forProduct($product)->create();

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    $item = $result->items->first();

    expect($item->media?->id)->toBe($media->id);
});

test('returns variant thumbnail when variant exists', function () {
    $user = User::factory()->create();
    $product = Product::factory()->withVariants()->create();
    $variant = $product->variants()->first();
    $media = Media::factory()->uploaded()->create();
    (new SyncMediaAction())->handle($variant, [$media->id]);

    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderItem::factory()->forOrder($order)->forProduct($product)->create([
        'product_variant_id' => $variant->id,
        'media_id' => $media->id,
    ]);

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    $item = $result->items->first();

    expect($item->media?->id)->toBe($media->id);
});

test('aborts when order does not belong to user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $otherUser->id]);

    $query = new CustomerOrderQuery();
    $query->execute($order->id, $user);
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('returns order when user owns the order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    expect($result->id)->toBe($order->id);
});

test('includes order notes', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'notes' => 'Please leave at the front door',
    ]);

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    expect($result->notes)->toBe('Please leave at the front door');
});

test('loads tax details with required fields', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderTaxDetail::factory()->forOrder($order)->create([
        'tax_name' => 'VAT',
        'tax_rate' => 10.00,
        'taxable_amount' => 100.00,
        'tax_amount' => 10.00,
    ]);

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    expect($result->taxDetails)->toHaveCount(1)
        ->and($result->taxDetails->first()->tax_name)->toBe('VAT')
        ->and($result->taxDetails->first()->tax_rate)->toBe('10.00')
        ->and($result->taxDetails->first()->tax_amount)->toBe('10.0000');
});

test('includes refund_total in order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'refund_total' => 25.50,
    ]);

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    expect($result->refund_total)->toBe('25.5000');
});

test('loads refunds with items and order item details', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    $orderItem = OrderItem::factory()->forOrder($order)->create();
    $refund = OrderRefund::factory()->completed()->create([
        'order_id' => $order->id,
        'reason' => 'Item was damaged',
    ]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $refund->id,
        'order_item_id' => $orderItem->id,
    ]);

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    expect($result->relationLoaded('refunds'))->toBeTrue()
        ->and($result->refunds)->toHaveCount(1)
        ->and($result->refunds->first()->id)->toBe($refund->id)
        ->and($result->refunds->first()->reason)->toBe('Item was damaged')
        ->and($result->refunds->first()->relationLoaded('items'))->toBeTrue()
        ->and($result->refunds->first()->items)->toHaveCount(1)
        ->and($result->refunds->first()->items->first()->relationLoaded('orderItem'))->toBeTrue();
});

test('excludes sensitive fields from refunds', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    $orderItem = OrderItem::factory()->forOrder($order)->create();
    $refund = OrderRefund::factory()->totalOverridden()->create(['order_id' => $order->id]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $refund->id,
        'order_item_id' => $orderItem->id,
        'restock' => true,
    ]);

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    $loadedRefund = $result->refunds->first();
    $loadedItem = $loadedRefund->items->first();

    expect($loadedRefund->getAttributes())->not->toHaveKey('is_manual_total')
        ->and($loadedItem->getAttributes())->not->toHaveKey('restock');
});

test('orders shipments by latest first', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);

    $olderShipment = App\Models\OrderShipment::factory()->create([
        'order_id' => $order->id,
        'created_at' => now()->subDays(2),
    ]);
    $newerShipment = App\Models\OrderShipment::factory()->create([
        'order_id' => $order->id,
        'created_at' => now()->subDay(),
    ]);

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    expect($result->shipments->first()->id)->toBe($newerShipment->id)
        ->and($result->shipments->last()->id)->toBe($olderShipment->id);
});

test('excludes sensitive fields from shipments', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);

    App\Models\OrderShipment::factory()->create([
        'order_id' => $order->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    $loadedShipment = $result->shipments->first();

    expect($loadedShipment->getAttributes())->not->toHaveKey('user_id')
        ->and($loadedShipment->getAttributes())->not->toHaveKey('updated_at');
});

test('orders refunds by latest first', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    $olderRefund = OrderRefund::factory()->create([
        'order_id' => $order->id,
        'created_at' => now()->subDays(2),
    ]);
    $newerRefund = OrderRefund::factory()->create([
        'order_id' => $order->id,
        'created_at' => now()->subDay(),
    ]);

    $query = new CustomerOrderQuery();
    $result = $query->execute($order->id, $user);

    expect($result->refunds->first()->id)->toBe($newerRefund->id)
        ->and($result->refunds->last()->id)->toBe($olderRefund->id);
});

test('exposes requires_shipping so digital items can be grouped apart', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderItem::factory()->forOrder($order)->create(['requires_shipping' => false]);

    $result = (new CustomerOrderQuery())->execute($order->id, $user);

    expect($result->items->first()->getAttributes())->toHaveKey('requires_shipping')
        ->and($result->items->first()->requires_shipping)->toBeFalse();
});
