<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\DiscountLinkController;

use function Pest\Laravel\get;

covers([
    DiscountLinkController::class,
]);

uses()->group('checkout');

test('stores coupon code in session and redirects home', function () {
    get(route('discount.apply', ['code' => 'SAVE10']))
        ->assertRedirect(route('home'))
        ->assertSessionHas('pending_coupon', 'SAVE10');
});

test('trims whitespace from the coupon code', function () {
    get(route('discount.apply', ['code' => '  SAVE10  ']))
        ->assertRedirect(route('home'))
        ->assertSessionHas('pending_coupon', 'SAVE10');
});
