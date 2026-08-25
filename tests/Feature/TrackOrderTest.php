<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\TrackOrderController;
use App\Http\Requests\Storefront\TrackOrderRequest;
use App\Models\Order;
use App\Queries\TrackedOrderQuery;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers(TrackOrderController::class, TrackOrderRequest::class, TrackedOrderQuery::class);

uses()->group('orders');

test('the track order form renders', function () {
    get(route('orders.track'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/orders/track')
            ->where('order', null));
});

test('an order can be looked up with its number and email', function () {
    $order = Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
    ]);

    post(route('orders.track.lookup'), [
        'order_number' => $order->id,
        'email' => 'guest@example.com',
    ])->assertRedirectToSignedRoute('orders.track.show', ['order' => $order->id]);
});

test('the lookup matches the email case-insensitively', function () {
    $order = Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
    ]);

    post(route('orders.track.lookup'), [
        'order_number' => $order->id,
        'email' => 'GUEST@EXAMPLE.COM',
    ])->assertRedirectToSignedRoute('orders.track.show', ['order' => $order->id]);
});

test('the lookup strips a leading hash from the order number', function () {
    $order = Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
    ]);

    post(route('orders.track.lookup'), [
        'order_number' => '#' . $order->id,
        'email' => 'guest@example.com',
    ])->assertRedirectToSignedRoute('orders.track.show', ['order' => $order->id]);
});

test('the lookup fails when the email does not match', function () {
    $order = Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
    ]);

    post(route('orders.track.lookup'), [
        'order_number' => $order->id,
        'email' => 'someone@example.com',
    ])->assertSessionHasErrors('order_number');
});

test('the lookup fails for an unknown order number', function () {
    post(route('orders.track.lookup'), [
        'order_number' => 999999,
        'email' => 'guest@example.com',
    ])->assertSessionHasErrors('order_number');
});

test('the order number and email are required', function () {
    post(route('orders.track.lookup'), [])
        ->assertSessionHasErrors(['order_number', 'email']);
});

test('a signed url renders the order status', function () {
    $order = Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
    ]);

    get(URL::signedRoute('orders.track.show', ['order' => $order->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/orders/track')
            ->where('order.id', $order->id));
});

test('an unsigned show request is forbidden', function () {
    $order = Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
    ]);

    get(route('orders.track.show', ['order' => $order->id]))
        ->assertForbidden();
});

test('a signed url for an unknown order returns 404', function () {
    get(URL::signedRoute('orders.track.show', ['order' => 999999]))
        ->assertNotFound();
});
