<?php

declare(strict_types=1);

use App\Enums\CheckoutSessionStatus;
use App\Http\Controllers\Storefront\CheckoutCancelController;
use App\Models\CheckoutSession;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\postJson;

covers(CheckoutCancelController::class);

uses()->group('checkout');

test('cancels a pending checkout session via ajax cancel endpoint', function () {
    $session = CheckoutSession::factory()->pending()->create();

    postJson(URL::signedRoute('checkout.cancel.store', ['session' => $session->id]))
        ->assertOk()
        ->assertJson(['canceled' => true]);

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Canceled);
});

test('handles already completed checkout session gracefully', function () {
    $session = CheckoutSession::factory()->completed()->create();

    postJson(URL::signedRoute('checkout.cancel.store', ['session' => $session->id]))
        ->assertOk()
        ->assertJson(['canceled' => true]);

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Completed);
});

test('handles already canceled checkout session gracefully', function () {
    $session = CheckoutSession::factory()->canceled()->create();

    postJson(URL::signedRoute('checkout.cancel.store', ['session' => $session->id]))
        ->assertOk()
        ->assertJson(['canceled' => true]);

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Canceled);
});

test('rejects unsigned request to ajax cancel endpoint', function () {
    $session = CheckoutSession::factory()->pending()->create();

    postJson(route('checkout.cancel.store', ['session' => $session->id]))
        ->assertForbidden();
});
