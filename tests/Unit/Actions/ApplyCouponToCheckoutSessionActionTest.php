<?php

declare(strict_types=1);

use App\Actions\ApplyCouponToCheckoutSessionAction;
use App\Models\CheckoutSession;
use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

covers(ApplyCouponToCheckoutSessionAction::class);

uses()->group('actions', 'abandoned-checkout');

test('rejects applying a coupon to a completed session', function () {
    $coupon = Coupon::factory()->valid()->fixed(10)->create();
    $session = CheckoutSession::factory()->completed()->create([
        'subtotal' => '100.0000',
    ]);

    expect(fn () => app(ApplyCouponToCheckoutSessionAction::class)->handle($session, $coupon->code))
        ->toThrow(ValidationException::class);
});
