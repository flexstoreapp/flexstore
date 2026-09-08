<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\Admin;
use App\Http\Middleware\Api\EnsureCustomerIsNotStaff;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('auth/login', [Admin\AdminAccessTokenController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('auth.login');

    Route::post('auth/two-factor', Admin\AdminTwoFactorChallengeController::class)
        ->middleware(['auth:sanctum', 'ability:' . TokenAbility::TwoFactorPending->value, 'throttle:6,1'])
        ->name('auth.two-factor');

    Route::middleware(['auth:sanctum', 'ability:' . TokenAbility::Admin->value])->group(function (): void {
        Route::post('auth/logout', [Admin\AdminAccessTokenController::class, 'destroy'])->name('auth.logout');
        Route::get('auth/me', Admin\AdminProfileController::class)->name('auth.me');

        Route::get('config', Admin\StoreConfigController::class)->name('config');

        // media
        Route::post('media', [Admin\MediaController::class, 'store'])->middleware(Authorize::using(Permission::MediaUpload))->name('media.store');

        // categories
        Route::get('categories/search', Admin\CategorySearchController::class)->middleware(Authorize::using(Permission::CategoriesReference))->name('categories.search');
        Route::get('categories', [Admin\CategoryController::class, 'index'])->middleware(Authorize::using(Permission::CategoriesView))->name('categories.index');
        Route::post('categories', [Admin\CategoryController::class, 'store'])->middleware(Authorize::using(Permission::CategoriesManage))->name('categories.store');
        Route::get('categories/{category}', [Admin\CategoryController::class, 'show'])->middleware(Authorize::using(Permission::CategoriesView))->name('categories.show');
        Route::patch('categories/{category}/reorder', [Admin\CategoryReorderController::class, 'update'])->middleware(Authorize::using(Permission::CategoriesManage))->name('categories.reorder');
        Route::patch('categories/{category}', [Admin\CategoryController::class, 'update'])->middleware(Authorize::using(Permission::CategoriesManage))->name('categories.update');
        Route::delete('categories/{category}', [Admin\CategoryController::class, 'destroy'])->middleware(Authorize::using(Permission::CategoriesDelete))->name('categories.destroy');

        // brands
        Route::get('brands/search', Admin\BrandSearchController::class)->middleware(Authorize::using(Permission::BrandsReference))->name('brands.search');
        Route::get('brands', [Admin\BrandController::class, 'index'])->middleware(Authorize::using(Permission::BrandsView))->name('brands.index');
        Route::post('brands', [Admin\BrandController::class, 'store'])->middleware(Authorize::using(Permission::BrandsManage))->name('brands.store');
        Route::delete('brands/bulk', [Admin\BulkBrandController::class, 'destroy'])->middleware(Authorize::using(Permission::BrandsDelete))->name('brands.bulk.destroy');
        Route::get('brands/{brand}', [Admin\BrandController::class, 'show'])->middleware(Authorize::using(Permission::BrandsView))->name('brands.show');
        Route::patch('brands/{brand}', [Admin\BrandController::class, 'update'])->middleware(Authorize::using(Permission::BrandsManage))->name('brands.update');
        Route::delete('brands/{brand}', [Admin\BrandController::class, 'destroy'])->middleware(Authorize::using(Permission::BrandsDelete))->name('brands.destroy');

        // products
        Route::get('products/search', Admin\ProductSearchController::class)->middleware(Authorize::using(Permission::ProductsReference))->name('products.search');
        Route::delete('products/bulk', [Admin\BulkProductController::class, 'destroy'])->middleware(Authorize::using(Permission::ProductsDelete))->name('products.bulk.destroy');
        Route::get('products', [Admin\ProductController::class, 'index'])->middleware(Authorize::using(Permission::ProductsView))->name('products.index');
        Route::post('products', [Admin\ProductController::class, 'store'])->middleware(Authorize::using(Permission::ProductsManage))->name('products.store');
        Route::get('products/{product}', [Admin\ProductController::class, 'show'])->middleware(Authorize::using(Permission::ProductsView))->name('products.show');
        Route::patch('products/{product}', [Admin\ProductController::class, 'update'])->middleware(Authorize::using(Permission::ProductsManage))->name('products.update');
        Route::delete('products/{product}', [Admin\ProductController::class, 'destroy'])->middleware(Authorize::using(Permission::ProductsDelete))->name('products.destroy');
        Route::post('products/{product}/duplicate', [Admin\DuplicateProductController::class, 'store'])->middleware(Authorize::using(Permission::ProductsManage))->name('products.duplicate');
        Route::post('product-downloads', [Admin\DigitalFileController::class, 'store'])->middleware(Authorize::using(Permission::ProductsManage))->name('product-downloads.store');

        // inventory
        Route::get('inventory', [Admin\InventoryController::class, 'index'])->middleware(Authorize::using(Permission::InventoryView))->name('inventory.index');
        Route::get('inventory/{product}', [Admin\InventoryController::class, 'show'])->middleware(Authorize::using(Permission::InventoryView))->name('inventory.show');
        Route::post('inventory/stock-adjustments', [Admin\StockAdjustmentController::class, 'store'])->middleware(Authorize::using(Permission::InventoryManage))->name('inventory.stock-adjustments.store');

        // customers
        Route::get('customers', [Admin\CustomerController::class, 'index'])->middleware(Authorize::using(Permission::CustomersView))->name('customers.index');
        Route::get('customers/{customer}', [Admin\CustomerController::class, 'show'])->middleware(Authorize::using(Permission::CustomersView))->middleware(EnsureCustomerIsNotStaff::class)->name('customers.show');
        Route::get('customers/{customer}/addresses', [Admin\CustomerAddressController::class, 'index'])->middleware(Authorize::using(Permission::CustomersView))->middleware(EnsureCustomerIsNotStaff::class)->name('customers.addresses.index');
        Route::post('customers', [Admin\CustomerController::class, 'store'])->middleware(Authorize::using(Permission::CustomersManage))->name('customers.store');
        Route::patch('customers/{customer}', [Admin\CustomerController::class, 'update'])->middleware(Authorize::using(Permission::CustomersManage))->middleware(EnsureCustomerIsNotStaff::class)->name('customers.update');
        Route::post('customers/{customer}/addresses', [Admin\CustomerAddressController::class, 'store'])->middleware(Authorize::using(Permission::CustomersManage))->middleware(EnsureCustomerIsNotStaff::class)->name('customers.addresses.store');
        Route::patch('customers/{customer}/addresses/{address}', [Admin\CustomerAddressController::class, 'update'])->middleware(Authorize::using(Permission::CustomersManage))->scopeBindings()->middleware(EnsureCustomerIsNotStaff::class)->name('customers.addresses.update');
        Route::delete('customers/{customer}/addresses/{address}', [Admin\CustomerAddressController::class, 'destroy'])->middleware(Authorize::using(Permission::CustomersManage))->scopeBindings()->middleware(EnsureCustomerIsNotStaff::class)->name('customers.addresses.destroy');
        Route::post('customers/{customer}/addresses/{address}/default', Admin\SetDefaultCustomerAddressController::class)->middleware(Authorize::using(Permission::CustomersManage))->scopeBindings()->middleware(EnsureCustomerIsNotStaff::class)->name('customers.addresses.default');
        Route::delete('customers/bulk', [Admin\BulkCustomerController::class, 'destroy'])->middleware(Authorize::using(Permission::CustomersDelete))->name('customers.bulk.destroy');
        Route::delete('customers/{customer}', [Admin\CustomerController::class, 'destroy'])->middleware(Authorize::using(Permission::CustomersDelete))->middleware(EnsureCustomerIsNotStaff::class)->name('customers.destroy');
        Route::get('users/search', Admin\UserSearchController::class)->middleware(Authorize::using(Permission::UsersReference))->name('users.search');

        // coupons
        Route::post('coupons/validate', Admin\CouponValidationController::class)->middleware(Authorize::using(Permission::OrdersManage))->name('coupons.validate');
        Route::delete('coupons/bulk', [Admin\BulkCouponController::class, 'destroy'])->middleware(Authorize::using(Permission::CouponsDelete))->name('coupons.bulk.destroy');
        Route::get('coupons', [Admin\CouponController::class, 'index'])->middleware(Authorize::using(Permission::CouponsView))->name('coupons.index');
        Route::post('coupons', [Admin\CouponController::class, 'store'])->middleware(Authorize::using(Permission::CouponsManage))->name('coupons.store');
        Route::get('coupons/{coupon}', [Admin\CouponController::class, 'show'])->middleware(Authorize::using(Permission::CouponsView))->name('coupons.show');
        Route::patch('coupons/{coupon}', [Admin\CouponController::class, 'update'])->middleware(Authorize::using(Permission::CouponsManage))->name('coupons.update');
        Route::delete('coupons/{coupon}', [Admin\CouponController::class, 'destroy'])->middleware(Authorize::using(Permission::CouponsDelete))->name('coupons.destroy');

        // reviews
        Route::delete('reviews/bulk', [Admin\BulkReviewController::class, 'destroy'])->middleware(Authorize::using(Permission::ReviewsDelete))->name('reviews.bulk.destroy');
        Route::get('reviews', [Admin\ReviewController::class, 'index'])->middleware(Authorize::using(Permission::ReviewsView))->name('reviews.index');
        Route::post('reviews', [Admin\ReviewController::class, 'store'])->middleware(Authorize::using(Permission::ReviewsManage))->name('reviews.store');
        Route::post('reviews/approve', [Admin\ReviewApproveController::class, 'store'])->middleware(Authorize::using(Permission::ReviewsManage))->name('reviews.approve');
        Route::post('reviews/reject', [Admin\ReviewRejectController::class, 'store'])->middleware(Authorize::using(Permission::ReviewsManage))->name('reviews.reject');
        Route::get('reviews/{review}', [Admin\ReviewController::class, 'show'])->middleware(Authorize::using(Permission::ReviewsView))->name('reviews.show');
        Route::patch('reviews/{review}', [Admin\ReviewController::class, 'update'])->middleware(Authorize::using(Permission::ReviewsManage))->name('reviews.update');
        Route::delete('reviews/{review}', [Admin\ReviewController::class, 'destroy'])->middleware(Authorize::using(Permission::ReviewsDelete))->name('reviews.destroy');

        // dashboard
        Route::get('dashboard', Admin\DashboardController::class)->middleware(Authorize::using(Permission::DashboardView))->name('dashboard');

        // orders
        Route::get('orders', [Admin\AdminOrderController::class, 'index'])->middleware(Authorize::using(Permission::OrdersView))->name('orders.index');
        Route::get('orders/{order}', [Admin\AdminOrderController::class, 'show'])->middleware(Authorize::using(Permission::OrdersView))->name('orders.show');
        Route::get('orders/{order}/activities', [Admin\AdminOrderActivityController::class, 'index'])->middleware(Authorize::using(Permission::OrdersView))->name('orders.activities.index');
        Route::post('orders/{order}/activities', [Admin\AdminOrderActivityController::class, 'store'])->middleware(Authorize::using(Permission::OrdersManage))->name('orders.activities.store');
        Route::patch('orders/{order}/activities/{activity}', [Admin\AdminOrderActivityController::class, 'update'])->middleware(Authorize::using(Permission::OrdersManage))->scopeBindings()->name('orders.activities.update');
        Route::delete('orders/{order}/activities/{activity}', [Admin\AdminOrderActivityController::class, 'destroy'])->middleware(Authorize::using(Permission::OrdersManage))->scopeBindings()->name('orders.activities.destroy');
        Route::post('orders/{order}/hold', Admin\AdminHoldOrderController::class)->middleware(Authorize::using(Permission::OrdersManage))->name('orders.hold');
        Route::post('orders/{order}/release-hold', Admin\AdminReleaseOrderHoldController::class)->middleware(Authorize::using(Permission::OrdersManage))->name('orders.release-hold');
        Route::post('orders/{order}/in-progress', Admin\AdminInProgressOrderController::class)->middleware(Authorize::using(Permission::OrdersManage))->name('orders.in-progress');
        Route::post('orders/{order}/resend-notification', Admin\AdminResendOrderNotificationController::class)->middleware(Authorize::using(Permission::OrdersManage))->name('orders.resend-notification');
        Route::post('orders/{order}/cancel', Admin\AdminCancelOrderController::class)->middleware(Authorize::using(Permission::OrdersCancel))->name('orders.cancel');
    });
});
