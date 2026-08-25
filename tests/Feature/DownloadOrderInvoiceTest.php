<?php

declare(strict_types=1);

use App\Enums\Country;
use App\Http\Controllers\Storefront\OrderInvoiceController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Utilities\InvoicePdfGenerator;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

covers(OrderInvoiceController::class, InvoicePdfGenerator::class);

uses()->group('account', 'order', 'invoice');

beforeEach(function () {
    Setting::setValue('store_country_code', Country::US->name);
});

test('customer can download the invoice for their own order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderItem::factory()->count(2)->forOrder($order)->create();

    $response = actingAs($user)->get(route('account.orders.invoice', $order));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain("invoice-{$order->id}.pdf");
});

test('invoice download requires authentication', function () {
    $order = Order::factory()->create();

    get(route('account.orders.invoice', $order))
        ->assertRedirect(route('account.login'));
});

test('customer cannot download the invoice for another customers order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => User::factory()->create()->id]);

    actingAs($user)
        ->get(route('account.orders.invoice', $order))
        ->assertNotFound();
});
