<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\OrderController;
use App\Models\Order;
use App\Models\User;
use App\Queries\CustomerOrderQuery;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

covers(OrderController::class, CustomerOrderQuery::class);

uses()->group('account');

test('order show requires authentication', function () {
    $order = Order::factory()->create();

    get(route('account.orders.show', $order))
        ->assertRedirect(route('account.login'));
});

test('accessing other users order returns not found', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $otherUser->id]);

    actingAs($user)
        ->get(route('account.orders.show', $order))
        ->assertNotFound();
});
