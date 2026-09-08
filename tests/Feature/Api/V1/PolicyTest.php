<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\PolicyController;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Setting;

use function Pest\Laravel\getJson;

covers(PolicyController::class, PolicyResource::class);

uses()->group('api');

test('a written policy is returned with its title', function (): void {
    Setting::setValue('refund_policy', 'Return anything unworn within 30 days.');

    getJson(route('api.v1.policies.show', 'refund'))
        ->assertOk()
        ->assertJsonPath('policy', 'refund')
        ->assertJsonPath('title', 'Refund policy')
        ->assertJsonPath('content', 'Return anything unworn within 30 days.');
});

test('a policy the merchant has not written is not found', function (): void {
    Setting::setValue('privacy_policy', '   ');

    getJson(route('api.v1.policies.show', 'privacy'))->assertNotFound();
    getJson(route('api.v1.policies.show', 'terms'))->assertNotFound();
});

test('an unknown policy is not found', function (): void {
    getJson(route('api.v1.policies.show', 'shipping'))->assertNotFound();
});

test('the configuration lists only the policies that have been written', function (): void {
    Setting::setValue('refund_policy', 'Return anything unworn within 30 days.');
    Setting::setValue('terms_of_service', 'These terms govern your use of the store.');

    getJson(route('api.v1.config'))
        ->assertOk()
        ->assertJsonPath('policies', ['refund', 'terms']);
});
