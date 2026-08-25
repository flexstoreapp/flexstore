<?php

declare(strict_types=1);

use App\Enums\Country;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\OrderInvoiceController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Utilities\InvoicePdfGenerator;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(OrderInvoiceController::class, InvoicePdfGenerator::class);

uses()->group('order', 'invoice');

beforeEach(function () {
    Setting::setValue('store_country_code', Country::US->name);
});

test('generates pdf invoice for order', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

    $response = actingAsSuperAdmin()->get(route('admin.orders.invoice', $order));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('invoice contains order id', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $response = actingAsSuperAdmin()->get(route('admin.orders.invoice', $order));

    $response->assertOk();

    $contentDisposition = $response->headers->get('content-disposition');
    expect($contentDisposition)->toContain("invoice-{$order->id}.pdf");
});

test('requires authentication', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $response = get(route('admin.orders.invoice', $order));

    $response->assertRedirect(route('admin.login'));
});

test('requires orders.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $response = actingAsAdmin()->get(route('admin.orders.invoice', $order));

    $response->assertOk();

    $role->revokePermissionTo(Permission::OrdersView);

    $response = actingAsAdmin()->get(route('admin.orders.invoice', $order));

    $response->assertForbidden();
});

test('returns 404 for non-existent order', function () {
    $response = actingAsSuperAdmin()->get(route('admin.orders.invoice', ['order' => 999999]));

    $response->assertNotFound();
});
