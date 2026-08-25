<?php

declare(strict_types=1);

use App\Models\StockReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(StockReservation::class);

uses()->group('models', 'stock');

test('has factory', function () {
    expect(StockReservation::factory())->toBeInstanceOf(Factory::class);
});

test('does not use updated_at timestamp', function () {
    expect(StockReservation::UPDATED_AT)->toBeNull();
});

test('casts expires_at to datetime', function () {
    $reservation = StockReservation::factory()->create();

    expect($reservation->expires_at)->toBeInstanceOf(Carbon\CarbonImmutable::class);
});

test('casts method returns expected map', function () {
    expect((new StockReservation())->casts())->toHaveKey('expires_at', 'datetime');
});

test('does not write updated_at when saving', function () {
    $reservation = StockReservation::factory()->create();

    expect($reservation->getAttribute('updated_at'))->toBeNull();
});
