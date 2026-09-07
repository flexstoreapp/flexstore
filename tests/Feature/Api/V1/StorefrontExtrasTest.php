<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AddressFieldRulesController;

use function Pest\Laravel\getJson;

covers(AddressFieldRulesController::class);

uses()->group('api');

test('address field rules describe what a country requires', function (): void {
    $response = getJson(route('api.v1.address-field-rules', 'US'));

    $response->assertOk()->assertJsonStructure(['data']);

    expect($response->json('data'))->toBeArray()->not->toBeEmpty();
});

test('the country code is case insensitive', function (): void {
    expect(getJson(route('api.v1.address-field-rules', 'us'))->json('data'))
        ->toBe(getJson(route('api.v1.address-field-rules', 'US'))->json('data'));
});
