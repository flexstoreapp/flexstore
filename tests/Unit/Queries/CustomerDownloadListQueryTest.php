<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItemDownload;
use App\Models\User;
use App\Queries\CustomerDownloadListQuery;

covers(CustomerDownloadListQuery::class);

uses()->group('queries', 'account', 'downloads');

test('returns only the customer own downloads', function () {
    $user = User::factory()->create();
    OrderItemDownload::factory()->count(2)->create(['customer_id' => $user->id]);
    OrderItemDownload::factory()->create(['customer_id' => User::factory()->create()->id]);

    $result = app(CustomerDownloadListQuery::class)->execute($user);

    expect($result->total())->toBe(2);
});

test('excludes downloads from canceled or refunded orders', function () {
    $user = User::factory()->create();

    OrderItemDownload::factory()->create(['customer_id' => $user->id, 'order_id' => Order::factory()->paid()]);
    OrderItemDownload::factory()->create([
        'customer_id' => $user->id,
        'order_id' => Order::factory()->create(['canceled_at' => now()]),
    ]);
    OrderItemDownload::factory()->create([
        'customer_id' => $user->id,
        'order_id' => Order::factory()->create(['payment_status' => PaymentStatus::Refunded]),
    ]);

    $result = app(CustomerDownloadListQuery::class)->execute($user);

    expect($result->total())->toBe(1);
});

test('appends download availability attributes', function () {
    $user = User::factory()->create();
    OrderItemDownload::factory()->create(['customer_id' => $user->id]);

    $download = app(CustomerDownloadListQuery::class)->execute($user)->getCollection()->first();

    expect($download->toArray())
        ->toHaveKeys(['is_available']);
});
