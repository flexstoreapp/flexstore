<?php

declare(strict_types=1);

use App\Actions\RemoveCouponFromCheckoutSessionAction;
use App\Models\CheckoutSession;
use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

covers(RemoveCouponFromCheckoutSessionAction::class);

uses()->group('actions', 'abandoned-checkout');

test('rejects removing a coupon from a completed session', function () {
    $coupon = Coupon::factory()->valid()->fixed(10)->create();
    $session = CheckoutSession::factory()->completed()->create([
        'coupon_id' => $coupon->id,
        'coupon_code' => $coupon->code,
        'discount_total' => '10.0000',
    ]);

    expect(fn () => app(RemoveCouponFromCheckoutSessionAction::class)->handle($session))
        ->toThrow(function (ValidationException $exception): void {
            expect($exception->errors()['coupon_code'][0])->toBe(__('This coupon cannot be removed from the checkout.'));
        });
});
