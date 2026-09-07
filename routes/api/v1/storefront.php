<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', [V1\RegisterController::class, 'store'])->middleware('throttle:10,1')->name('auth.register');
Route::post('auth/login', [V1\AccessTokenController::class, 'store'])->middleware('throttle:10,1')->name('auth.login');
Route::post('auth/forgot-password', [V1\PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('auth.forgot-password');
Route::post('auth/reset-password', [V1\NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('auth.reset-password');

Route::get('config', V1\StoreConfigController::class)->name('config');
Route::get('homepage', V1\HomepageController::class)->name('homepage');
Route::get('address-field-rules/{country}', V1\AddressFieldRulesController::class)->name('address-field-rules');
Route::get('policies/{policy}', [V1\PolicyController::class, 'show'])->name('policies.show');

Route::post('orders/track', V1\TrackOrderController::class)->middleware('throttle:10,1')->name('orders.track');

Route::get('shop', [V1\ProductController::class, 'index'])->name('shop.index');
Route::get('shop/facets', V1\ProductFacetController::class)->name('shop.facets');
Route::get('search/suggestions', V1\SearchSuggestionController::class)->name('search.suggestions');
Route::get('products/{product:url_handle}', [V1\ProductController::class, 'show'])->name('products.show');
Route::get('products/{product:url_handle}/related', V1\RelatedProductController::class)->name('products.related');
Route::get('products/{product:url_handle}/up-sells', V1\UpSellProductController::class)->name('products.up-sells');
Route::get('products/{product:url_handle}/reviews', [V1\ProductReviewController::class, 'index'])->name('products.reviews.index');

Route::get('categories', [V1\CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{category:url_handle}/products', [V1\CategoryProductController::class, 'index'])->name('categories.products.index');

Route::get('brands', [V1\BrandController::class, 'index'])->name('brands.index');
Route::get('brands/{brand:url_handle}/products', [V1\BrandProductController::class, 'index'])->name('brands.products.index');

Route::get('cart', [V1\CartController::class, 'show'])->name('cart.show');
Route::delete('cart', [V1\CartController::class, 'destroy'])->name('cart.destroy');
Route::get('cart/cross-sells', V1\CartCrossSellController::class)->name('cart.cross-sells');
Route::post('cart/items', [V1\CartItemController::class, 'store'])->name('cart.items.store');
Route::patch('cart/items/{cartItem}', [V1\CartItemController::class, 'update'])->name('cart.items.update');
Route::delete('cart/items/{cartItem}', [V1\CartItemController::class, 'destroy'])->name('cart.items.destroy');

Route::post('checkout/shipping-options', [V1\CheckoutShippingOptionController::class, 'index'])->name('checkout.shipping-options');
Route::post('checkout/payment-options', [V1\CheckoutPaymentOptionController::class, 'index'])->name('checkout.payment-options');
Route::post('checkout/coupons', [V1\CheckoutCouponController::class, 'store'])->name('checkout.coupons.store');
Route::delete('checkout/coupons', [V1\CheckoutCouponController::class, 'destroy'])->name('checkout.coupons.destroy');
Route::post('checkout', [V1\CheckoutController::class, 'store'])->middleware('checkout.guest')->name('checkout.store');
Route::patch('checkout/draft', [V1\CheckoutDraftController::class, 'update'])->middleware('throttle:60,1')->name('checkout.draft.update');
Route::post('checkout/options', [V1\CheckoutOptionController::class, 'store'])->name('checkout.options.store');
Route::get('checkout/sessions/{session}', [V1\CheckoutSessionController::class, 'show'])->name('checkout.sessions.show');
Route::delete('checkout/sessions/{session}', [V1\CheckoutSessionController::class, 'destroy'])->name('checkout.sessions.destroy');

Route::middleware(['auth:sanctum', 'ability:' . TokenAbility::Customer->value])->group(function (): void {
    Route::post('auth/logout', [V1\AccessTokenController::class, 'destroy'])->name('auth.logout');
    Route::post('auth/email/verification-notification', V1\EmailVerificationNotificationController::class)
        ->middleware('throttle:5,1')
        ->name('auth.verification.send');
    Route::post('auth/email/verify', V1\VerifyEmailController::class)
        ->middleware('throttle:10,1')
        ->name('auth.verification.verify');

    Route::get('account/profile', [V1\ProfileController::class, 'show'])->name('account.profile.show');
    Route::patch('account/profile', [V1\ProfileController::class, 'update'])->name('account.profile.update');
    Route::delete('account/profile', [V1\ProfileController::class, 'destroy'])->name('account.profile.destroy');
    Route::put('account/password', [V1\PasswordController::class, 'update'])->name('account.password.update');

    Route::post('products/{product:url_handle}/reviews', [V1\ProductReviewController::class, 'store'])->name('products.reviews.store');

    Route::get('account/orders', [V1\OrderController::class, 'index'])->name('account.orders.index');
    Route::get('account/orders/{order}', [V1\OrderController::class, 'show'])->name('account.orders.show');
    Route::get('account/orders/{order}/invoice', V1\OrderInvoiceController::class)->name('account.orders.invoice');

    Route::get('account/downloads', [V1\DownloadController::class, 'index'])->name('account.downloads.index');
    Route::get('account/downloads/{download:token}', [V1\DownloadController::class, 'show'])
        ->middleware('throttle:30,1')
        ->name('account.downloads.show');

    Route::get('account/addresses', [V1\AddressController::class, 'index'])->name('account.addresses.index');
    Route::post('account/addresses', [V1\AddressController::class, 'store'])->name('account.addresses.store');
    Route::patch('account/addresses/{address}', [V1\AddressController::class, 'update'])->name('account.addresses.update');
    Route::delete('account/addresses/{address}', [V1\AddressController::class, 'destroy'])->name('account.addresses.destroy');
    Route::post('account/addresses/{address}/default', V1\SetDefaultAddressController::class)->name('account.addresses.default');

    Route::get('account/wishlist', [V1\WishlistController::class, 'show'])->name('account.wishlist.show');
    Route::put('account/wishlist/items/{product}', [V1\WishlistItemController::class, 'update'])->name('account.wishlist.items.update');
    Route::delete('account/wishlist/items/{product}', [V1\WishlistItemController::class, 'destroy'])->name('account.wishlist.items.destroy');
});
