<?php

declare(strict_types=1);

use App\Models\PaymentGateway;
use App\Queries\ActivePaymentGatewayListQuery;
use Illuminate\Database\Eloquent\Collection;

covers(ActivePaymentGatewayListQuery::class);

uses()->group('queries', 'payment-gateway');

test('returns only active payment gateways', function () {
    $query = new ActivePaymentGatewayListQuery();
    $result = $query->execute();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->every(fn (PaymentGateway $gateway): bool => $gateway->is_active))->toBeTrue();
});

test('excludes inactive gateways', function () {
    PaymentGateway::query()->update(['is_active' => false]);

    $query = new ActivePaymentGatewayListQuery();
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('includes active gateways', function () {
    PaymentGateway::query()->update(['is_active' => true]);
    $expectedCount = PaymentGateway::count();

    $query = new ActivePaymentGatewayListQuery();
    $result = $query->execute();

    expect($result)->toHaveCount($expectedCount);
});
