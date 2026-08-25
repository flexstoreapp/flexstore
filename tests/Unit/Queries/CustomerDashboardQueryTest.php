<?php

declare(strict_types=1);

use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use App\Queries\CustomerDashboardQuery;

covers(CustomerDashboardQuery::class);

uses()->group('queries', 'account');

test('returns the three most recent orders', function () {
    $user = User::factory()->create();
    Order::factory()->count(5)->create(['customer_id' => $user->id]);

    $result = app(CustomerDashboardQuery::class)->execute($user);

    expect($result['recentOrders'])->toHaveCount(3);
});

test('returns the default address only', function () {
    $user = User::factory()->create();
    $default = CustomerAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    CustomerAddress::factory()->create(['user_id' => $user->id, 'is_default' => false]);

    $result = app(CustomerDashboardQuery::class)->execute($user);

    expect($result['defaultAddress']?->id)->toBe($default->id);
});
