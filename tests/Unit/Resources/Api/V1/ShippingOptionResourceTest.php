<?php

declare(strict_types=1);

use App\Enums\ShippingRateType;
use App\Http\Resources\Api\V1\ShippingOptionResource;
use Illuminate\Http\Request;

covers(ShippingOptionResource::class);

uses()->group('resources', 'api');

test('a stored rate exposes its id as the rate id and carries no quote', function (): void {
    $option = (new ShippingOptionResource([
        'id' => 7,
        'name' => ['en' => 'Standard', 'ar' => 'عادي'],
        'carrier_name' => ['en' => 'In-house'],
        'type' => ShippingRateType::Flat,
        'rate' => '10.0000',
        'delivery_time' => ['en' => '3 to 5 days'],
    ]))->toArray(Request::create('/'));

    expect($option['id'])->toBe(7)
        ->and($option['rate_id'])->toBe(7)
        ->and($option['quote_reference'])->toBeNull()
        ->and($option['service_code'])->toBeNull()
        ->and($option['provider'])->toBeNull()
        ->and($option['type'])->toBe('flat')
        ->and($option['name'])->toBe('Standard')
        ->and($option['delivery_time'])->toBe('3 to 5 days');
});

test('a rate type already stored as a string is passed through', function (): void {
    $option = (new ShippingOptionResource([
        'id' => 1,
        'name' => ['en' => 'Free shipping'],
        'carrier_name' => ['en' => 'In-house'],
        'type' => 'free',
        'rate' => '0.0000',
        'delivery_time' => null,
    ]))->toArray(Request::create('/'));

    expect($option['type'])->toBe('free')
        ->and($option['delivery_time'])->toBeNull();
});

test('names fall back to the store locale when the active one is missing', function (): void {
    app()->setLocale('ar');

    $option = (new ShippingOptionResource([
        'id' => 1,
        'name' => ['en' => 'Standard'],
        'carrier_name' => ['en' => 'In-house'],
        'type' => ShippingRateType::Flat,
        'rate' => '10.0000',
        'delivery_time' => null,
    ]))->toArray(Request::create('/'));

    expect($option['name'])->toBe('Standard');
});
