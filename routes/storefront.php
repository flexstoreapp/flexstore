<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront;
use App\Http\Middleware\CheckStorefrontMaintenance;
use Illuminate\Support\Facades\Route;

Route::middleware(CheckStorefrontMaintenance::class)->group(function () {
    Route::get('/', [Storefront\HomepageController::class, 'show'])->name('home');

    Route::get('shop', [Storefront\ShopController::class, 'index'])->name('shop.index');
    Route::get('products/{product:url_handle}', [Storefront\ProductController::class, 'show'])->name('products.show');
    Route::get('products/{product:url_handle}/quick-view', [Storefront\ProductQuickViewController::class, 'show'])->name('products.quick-view');
    Route::post('products/{product}/reviews', [Storefront\ProductReviewController::class, 'store'])->name('products.reviews.store')->middleware('auth');
    Route::get('categories', [Storefront\CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{category:url_handle}', [Storefront\CategoryProductController::class, 'show'])->name('categories.products.show');
    Route::get('brands', [Storefront\BrandController::class, 'index'])->name('brands.index');
    Route::get('brands/{brand:url_handle}', [Storefront\BrandProductController::class, 'show'])->name('brands.products.show');
    Route::get('search', Storefront\SearchController::class)->name('search');

    Route::get('search/suggestions', Storefront\SearchSuggestionController::class)->name('search.suggestions');

    Route::put('wishlist/items/{product}', [Storefront\WishlistItemController::class, 'update'])->name('wishlist.items.update');
    Route::delete('wishlist/items/{product}', [Storefront\WishlistItemController::class, 'destroy'])->name('wishlist.items.destroy');
    Route::get('wishlist/{wishlist?}', [Storefront\WishlistController::class, 'show'])->name('wishlist.show');

    Route::get('policies/refund', [Storefront\RefundPolicyController::class, 'show'])->name('policies.refund');
    Route::get('policies/privacy', [Storefront\PrivacyPolicyController::class, 'show'])->name('policies.privacy');
    Route::get('policies/terms', [Storefront\TermsOfServiceController::class, 'show'])->name('policies.terms');

    Route::get('cart', [Storefront\CartController::class, 'show'])->name('cart.show');
    Route::delete('cart', [Storefront\CartController::class, 'destroy'])->name('cart.destroy');
    Route::post('cart/items', [Storefront\CartItemController::class, 'store'])->name('cart.items.store');
    Route::patch('cart/items/{cartItem}', [Storefront\CartItemController::class, 'update'])->name('cart.items.update');
    Route::delete('cart/items/{cartItem}', [Storefront\CartItemController::class, 'destroy'])->name('cart.items.destroy');

    Route::get('checkout/success', [Storefront\CheckoutSuccessController::class, 'show'])->name('checkout.success');
    Route::get('checkout/cancel', [Storefront\CheckoutCancelController::class, 'show'])->name('checkout.cancel');
    Route::post('checkout/sessions/{session}/cancel', [Storefront\CheckoutCancelController::class, 'store'])->name('checkout.cancel.store')->middleware('signed');
    Route::get('checkout', [Storefront\CheckoutController::class, 'create'])->middleware('checkout.guest')->name('checkout.create');
    Route::post('checkout', [Storefront\CheckoutController::class, 'store'])->middleware('checkout.guest')->name('checkout.store');
    Route::patch('checkout/draft', [Storefront\CheckoutDraftController::class, 'update'])->middleware('throttle:60,1')->name('checkout.draft.update');
    Route::post('checkout/shipping-options', [Storefront\CheckoutShippingOptionController::class, 'index'])->name('checkout.shipping-options.index');
    Route::post('checkout/payment-options', [Storefront\CheckoutPaymentOptionController::class, 'index'])->name('checkout.payment-options.index');
    Route::post('checkout/options/select', [Storefront\CheckoutOptionController::class, 'store'])->name('checkout.options.store');
    Route::post('checkout/coupons', [Storefront\CheckoutCouponController::class, 'store'])->name('checkout.coupons.store');
    Route::delete('checkout/coupons', [Storefront\CheckoutCouponController::class, 'destroy'])->name('checkout.coupons.destroy');

    Route::get('discount/{code}', Storefront\DiscountLinkController::class)->name('discount.apply');

    Route::get('downloads/{download:token}', [Storefront\DownloadController::class, 'show'])->middleware('throttle:30,1')->name('downloads.show');

    Route::get('track-order', [Storefront\TrackOrderController::class, 'create'])->name('orders.track');
    Route::post('track-order', [Storefront\TrackOrderController::class, 'store'])->middleware('throttle:10,1')->name('orders.track.lookup');
    Route::get('track-order/{order}', [Storefront\TrackOrderController::class, 'show'])->middleware('throttle:30,1')->name('orders.track.show');

    Route::prefix('account')->name('account.')->group(function () {
        Route::middleware('account.guest')->group(function () {
            Route::get('login', [Storefront\SessionController::class, 'create'])->name('login');
            Route::post('login', [Storefront\SessionController::class, 'store']);
            Route::get('register', [Storefront\RegisterController::class, 'create'])->name('register');
            Route::post('register', [Storefront\RegisterController::class, 'store']);
            Route::get('forgot-password', [Storefront\PasswordResetLinkController::class, 'create'])->name('password.request');
            Route::post('forgot-password', [Storefront\PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
            Route::get('reset-password/{token}', [Storefront\NewPasswordController::class, 'create'])->name('password.reset');
            Route::post('reset-password', [Storefront\NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.store');
        });

        Route::middleware('account.auth')->group(function () {
            Route::get('verify-email', Storefront\EmailVerificationNoticeController::class)->name('verification.notice');
            Route::get('verify-email/{id}/{hash}', Storefront\VerifyEmailController::class)
                ->middleware(['signed', 'throttle:5,1'])
                ->name('verification.verify');
            Route::post('verify-email/notification', Storefront\EmailVerificationNotificationController::class)
                ->middleware('throttle:5,1')
                ->name('verification.send');
            Route::post('logout', [Storefront\SessionController::class, 'destroy'])->name('logout');

            Route::get('/', [Storefront\DashboardController::class, 'index'])->name('dashboard');

            Route::get('profile', [Storefront\ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('profile', [Storefront\ProfileController::class, 'update'])->name('profile.update');
            Route::delete('profile', [Storefront\ProfileController::class, 'destroy'])->name('profile.destroy');

            Route::put('password', [Storefront\PasswordController::class, 'update'])->name('password.update');

            Route::get('orders', [Storefront\OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [Storefront\OrderController::class, 'show'])->name('orders.show');
            Route::get('orders/{order}/invoice', Storefront\OrderInvoiceController::class)->name('orders.invoice');

            Route::get('downloads', [Storefront\DownloadController::class, 'index'])->name('downloads.index');

            Route::get('addresses', [Storefront\AddressController::class, 'index'])->name('addresses.index');
            Route::post('addresses', [Storefront\AddressController::class, 'store'])->name('addresses.store');
            Route::patch('addresses/{address}', [Storefront\AddressController::class, 'update'])->name('addresses.update');
            Route::delete('addresses/{address}', [Storefront\AddressController::class, 'destroy'])->name('addresses.destroy');
            Route::post('addresses/{address}/default', Storefront\SetDefaultAddressController::class)->name('addresses.default');
        });
    });
});
