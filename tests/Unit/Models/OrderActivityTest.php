<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\OrderActivityType;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(OrderActivity::class);

uses()->group('models', 'order');

test('has factory', function () {
    expect(OrderActivity::factory())->toBeInstanceOf(Factory::class);
});

test('uses correct table name', function () {
    $activity = new OrderActivity();

    expect($activity->getTable())->toBe('order_activities');
});

test('casts attributes correctly', function () {
    $activity = new OrderActivity();
    $casts = $activity->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('type', OrderActivityType::class)
        ->toHaveKey('metadata', 'array');
});

test('belongs to order relationship', function () {
    $order = Order::factory()->create();
    $activity = OrderActivity::factory()->create(['order_id' => $order->id]);

    expect($activity->order)->toBeInstanceOf(Order::class)
        ->and($activity->order->id)->toBe($order->id);
});

test('belongs to user relationship', function () {
    $user = User::factory()->create();
    $activity = OrderActivity::factory()->create(['user_id' => $user->id]);

    expect($activity->user)->toBeInstanceOf(User::class)
        ->and($activity->user->id)->toBe($user->id);
});

test('can have null user', function () {
    $activity = OrderActivity::factory()->create(['user_id' => null]);

    expect($activity->user)->toBeNull();
});

test('sets created_at automatically', function () {
    $activity = OrderActivity::factory()->create();

    expect($activity->created_at)->not->toBeNull();
});
