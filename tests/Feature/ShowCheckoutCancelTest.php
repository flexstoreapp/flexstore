<?php

declare(strict_types=1);

use App\Enums\CheckoutSessionStatus;
use App\Http\Controllers\Storefront\CheckoutCancelController;
use App\Http\Requests\Storefront\ShowCheckoutCancelRequest;
use App\Models\CheckoutSession;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers(CheckoutCancelController::class, ShowCheckoutCancelRequest::class);

uses()->group('checkout');

test('cancels a pending checkout session when visiting cancel page', function () {
    $session = CheckoutSession::factory()->pending()->create();

    $response = get(URL::temporarySignedRoute('checkout.cancel', now()->addDay(), ['session' => $session->id]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/checkout/cancel')
        );

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Canceled);
});

test('does not cancel a completed checkout session', function () {
    $session = CheckoutSession::factory()->completed()->create();

    get(URL::temporarySignedRoute('checkout.cancel', now()->addDay(), ['session' => $session->id]))
        ->assertOk();

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Completed);
});

test('handles already canceled session gracefully', function () {
    $session = CheckoutSession::factory()->canceled()->create();

    get(URL::temporarySignedRoute('checkout.cancel', now()->addDay(), ['session' => $session->id]))
        ->assertOk();

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Canceled);
});

test('rejects unsigned request to cancel page', function () {
    $session = CheckoutSession::factory()->create();

    get(route('checkout.cancel', ['session' => $session->id]))
        ->assertForbidden();
});

test('returns 404 for non-existent session on cancel page', function () {
    get(URL::temporarySignedRoute('checkout.cancel', now()->addDay(), ['session' => fake()->uuid()]))
        ->assertNotFound();
});
