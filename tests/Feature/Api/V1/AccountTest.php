<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\EmailVerificationNotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderInvoiceController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SetDefaultAddressController;
use App\Http\Controllers\Api\V1\WishlistController;
use App\Http\Controllers\Api\V1\WishlistItemController;
use App\Http\Requests\Api\V1\DestroyProfileRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItem as OrderLineItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\CustomerVerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

covers(
    ProfileController::class,
    UpdateProfileRequest::class,
    OrderController::class,
    AddressController::class,
    SetDefaultAddressController::class,
    WishlistController::class,
    WishlistItemController::class,
    OrderInvoiceController::class,
    EmailVerificationNotificationController::class,
    DestroyProfileRequest::class,
);

uses()->group('api');

test('the profile is returned for the token owner', function (): void {
    $user = actingAsApiCustomer();

    getJson(route('api.v1.account.profile.show'))
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email);
});

test('the profile name can be updated without touching the email', function (): void {
    $user = actingAsApiCustomer();

    patchJson(route('api.v1.account.profile.update'), ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('name', 'New Name')
        ->assertJsonPath('email', $user->email);

    assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'email' => $user->email]);
});

test('a token without the customer ability is rejected', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['something-else']);

    getJson(route('api.v1.account.profile.show'))->assertForbidden();
});

test('orders are listed for the authenticated customer only', function (): void {
    $user = actingAsApiCustomer();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderItem::factory()->create(['order_id' => $order->id]);
    Order::factory()->create();

    getJson(route('api.v1.account.orders.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $order->id);
});

test('an order belonging to another customer is not readable', function (): void {
    actingAsApiCustomer();
    $order = Order::factory()->create();

    getJson(route('api.v1.account.orders.show', $order->id))->assertNotFound();
});

test('an order is returned with items, addresses and totals', function (): void {
    $user = actingAsApiCustomer();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    getJson(route('api.v1.account.orders.show', $order->id))
        ->assertOk()
        ->assertJsonPath('id', $order->id)
        ->assertJsonStructure(['items', 'subtotal', 'total', 'shipments', 'refunds', 'downloads']);
});

test('an address can be created, defaulted and deleted', function (): void {
    $user = actingAsApiCustomer();

    $addressId = postJson(route('api.v1.account.addresses.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => 'US',
    ])->assertCreated()->json('id');

    postJson(route('api.v1.account.addresses.default', $addressId))
        ->assertOk()
        ->assertJsonPath('is_default', true);

    deleteJson(route('api.v1.account.addresses.destroy', $addressId))->assertOk();

    expect(CustomerAddress::query()->where('user_id', $user->id)->count())->toBe(0);
});

test('an address belonging to another customer cannot be updated', function (): void {
    actingAsApiCustomer();
    $address = CustomerAddress::factory()->create();

    patchJson(route('api.v1.account.addresses.update', $address->id), ['city' => 'Hacked'])
        ->assertForbidden();
});

test('products can be added to and removed from the wishlist', function (): void {
    actingAsApiCustomer();
    $product = Product::factory()->available()->create();

    putJson(route('api.v1.account.wishlist.items.update', $product->id))->assertOk();

    getJson(route('api.v1.account.wishlist.show'))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $product->id);

    deleteJson(route('api.v1.account.wishlist.items.destroy', $product->id))->assertOk();

    getJson(route('api.v1.account.wishlist.show'))->assertOk()->assertJsonCount(0);
});

test('account endpoints reject unauthenticated requests', function (): void {
    getJson(route('api.v1.account.orders.index'))->assertUnauthorized();
});

test('saved addresses are listed for the authenticated customer', function (): void {
    $user = actingAsApiCustomer();
    $address = CustomerAddress::factory()->create(['user_id' => $user->id]);
    CustomerAddress::factory()->create();

    getJson(route('api.v1.account.addresses.index'))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $address->id);
});

test('deleting the account requires the current password and revokes every token', function (): void {
    $user = User::factory()->create(['password' => 'password-1234']);
    $user->createToken('Other device', [TokenAbility::Customer->value]);
    actingAsApiCustomer($user);

    deleteJson(route('api.v1.account.profile.destroy'), ['password' => 'wrong-password'])
        ->assertJsonValidationErrors('password');

    deleteJson(route('api.v1.account.profile.destroy'), ['password' => 'password-1234'])->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

test('a verification email is resent only while the address is unverified', function (): void {
    Notification::fake();
    $user = actingAsApiCustomer(User::factory()->unverified()->create());

    postJson(route('api.v1.auth.verification.send'))->assertOk();

    Notification::assertSentTo($user, CustomerVerifyEmailNotification::class);

    $user->markEmailAsVerified();
    Notification::fake();

    postJson(route('api.v1.auth.verification.send'))->assertOk();

    Notification::assertNothingSent();
});

test('an order line for a product that is gone is not linkable', function (): void {
    $user = actingAsApiCustomer();
    $inactive = Product::factory()->create(['is_active' => false]);
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderLineItem::factory()->create(['order_id' => $order->id, 'product_id' => $inactive->id]);

    getJson(route('api.v1.account.orders.show', $order->id))
        ->assertOk()
        ->assertJsonPath('items.0.url_handle', null);

    getJson(route('api.v1.account.orders.index'))
        ->assertOk()
        ->assertJsonPath('data.0.items.0.url_handle', null);
});

test('the invoice for an own order is downloadable as a pdf', function (): void {
    $user = actingAsApiCustomer();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderLineItem::factory()->count(2)->create(['order_id' => $order->id]);

    $response = getJson(route('api.v1.account.orders.invoice', $order->id));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain("invoice-{$order->id}.pdf");
});

test('the invoice for another customer order is not found', function (): void {
    actingAsApiCustomer();

    getJson(route('api.v1.account.orders.invoice', Order::factory()->create()->id))->assertNotFound();
});
