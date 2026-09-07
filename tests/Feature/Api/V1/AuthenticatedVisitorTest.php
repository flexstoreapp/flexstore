<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Http\Middleware\Api\UseSanctumGuard;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

covers(UseSanctumGuard::class);

uses()->group('api');

/**
 * These use a real bearer token rather than Sanctum::actingAs(), which sets the
 * guard itself and would hide a missing guard in the middleware stack.
 */
function bearer(User $user): array
{
    return ['Authorization' => 'Bearer ' . $user->createToken('test', [TokenAbility::Customer->value])->plainTextToken];
}

test('a bearer token identifies the customer on public routes', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    $token = postJson(route('api.v1.cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ], bearer($user))->assertOk()->json('data.token');

    expect(Cart::query()->findOrFail($token)->customer_id)->toBe($user->id);
});

test('a signed-in customer keeps their cart across requests without a cart token', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();
    $headers = bearer($user);

    postJson(route('api.v1.cart.items.store'), ['product_id' => $product->id, 'quantity' => 2], $headers)->assertOk();

    getJson(route('api.v1.cart.show'), $headers)
        ->assertOk()
        ->assertJsonPath('data.item_count', 2);
});

test('no token still resolves an anonymous visitor', function (): void {
    $product = Product::factory()->available()->create();

    $token = postJson(route('api.v1.cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertOk()->json('data.token');

    expect(Cart::query()->findOrFail($token)->customer_id)->toBeNull();
});

test('a bearer token still authenticates the account routes', function (): void {
    $user = User::factory()->create();

    getJson(route('api.v1.account.profile.show'), bearer($user))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});
