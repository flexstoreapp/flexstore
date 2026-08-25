<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Admin;
use App\Http\Middleware\RedirectIfNotAuthorized;
use App\Utilities\AdminPath;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\Route;

Route::prefix(AdminPath::prefix())->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\SessionController::class, 'create'])->name('login');
        Route::post('login', [Admin\SessionController::class, 'store']);
        Route::get('forgot-password', [Admin\PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [Admin\PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
        Route::get('reset-password/{token}', [Admin\NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [Admin\NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.store');
        Route::get('two-factor-challenge', [Admin\TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
        Route::post('two-factor-challenge', [Admin\TwoFactorChallengeController::class, 'store'])->middleware('throttle:6,1')->name('two-factor.challenge.store');
        Route::get('passkeys/login/options', [Admin\PasskeyLoginOptionController::class, 'show'])->name('passkeys.login.options');
        Route::post('passkeys/login', [Admin\PasskeyLoginController::class, 'store'])->middleware('throttle:6,1')->name('passkeys.login.store');
    });

    Route::middleware('auth')->group(function () {
        // license
        // auth
        Route::get('confirm-password', [Admin\ConfirmablePasswordController::class, 'show'])->name('password.confirm');
        Route::post('confirm-password', [Admin\ConfirmablePasswordController::class, 'store'])->middleware('throttle:6,1');
        Route::get('confirm-password/passkey/options', [Admin\PasskeyConfirmationOptionController::class, 'show'])->name('password.confirm.passkey.options');
        Route::post('confirm-password/passkey', [Admin\PasskeyConfirmationController::class, 'store'])->middleware('throttle:6,1')->name('password.confirm.passkey.store');
        Route::post('logout', [Admin\SessionController::class, 'destroy'])->name('logout');
        // dashboard
        Route::get('/', [Admin\DashboardController::class, 'index'])->middleware(RedirectIfNotAuthorized::using(Permission::DashboardView))->name('dashboard');
        // reports
        // media
        Route::post('media', [Admin\MediaController::class, 'store'])->middleware(Authorize::using(Permission::MediaUpload))->name('media.store');
        // digital product files
        Route::post('product-downloads', [Admin\DigitalFileController::class, 'store'])->middleware(Authorize::using(Permission::ProductsManage))->name('product-downloads.store');
        // digital product files
        // account
        Route::redirect('account', 'account/profile')->name('account');
        Route::get('account/profile', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('account/profile', [Admin\ProfileController::class, 'update'])->name('profile.update');
        Route::get('account/password', [Admin\PasswordController::class, 'edit'])->name('password.edit');
        Route::put('account/password', [Admin\PasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
        Route::patch('account/appearance', [Admin\AppearanceController::class, 'update'])->name('appearance.update');
        Route::middleware('password.confirm:admin.password.confirm')->group(function () {
            Route::get('account/security', [Admin\SecurityController::class, 'show'])->name('security.show');
            Route::post('account/two-factor', [Admin\TwoFactorAuthenticationController::class, 'store'])->name('two-factor.store');
            Route::delete('account/two-factor', [Admin\TwoFactorAuthenticationController::class, 'destroy'])->name('two-factor.destroy');
            Route::post('account/two-factor/confirm', [Admin\TwoFactorConfirmationController::class, 'store'])->name('two-factor.confirm');
            Route::get('account/two-factor/qr-code', [Admin\TwoFactorQrCodeController::class, 'show'])->name('two-factor.qr-code');
            Route::get('account/two-factor/secret-key', [Admin\TwoFactorSecretKeyController::class, 'show'])->name('two-factor.secret-key');
            Route::post('account/two-factor/recovery-codes', [Admin\TwoFactorRecoveryCodesController::class, 'store'])->name('two-factor.recovery-codes.store');
            Route::get('account/passkeys/options', [Admin\PasskeyRegistrationOptionController::class, 'show'])->name('passkeys.options');
            Route::post('account/passkeys', [Admin\PasskeyController::class, 'store'])->name('passkeys.store');
            Route::delete('account/passkeys/{passkey}', [Admin\PasskeyController::class, 'destroy'])->name('passkeys.destroy');
        });
        // orders
        Route::post('orders/calculate-taxes', Admin\OrderTaxCalculatorController::class)->middleware(Authorize::using(Permission::OrdersManage))->name('orders.calculate-taxes');
        Route::post('orders/shipping-options', Admin\OrderShippingOptionController::class)->middleware(Authorize::using(Permission::OrdersManage))->name('orders.shipping-options');
        Route::middleware(Authorize::using(Permission::OrdersManage))->group(function () {
            Route::get('orders/create', [Admin\OrderController::class, 'create'])->name('orders.create');
            Route::post('orders', [Admin\OrderController::class, 'store'])->name('orders.store');
            Route::get('orders/{order}/duplicate', [Admin\DuplicateOrderController::class, 'create'])->name('orders.duplicate');
        });
        Route::middleware(Authorize::using(Permission::OrdersView))->group(function () {
            Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
            Route::get('orders/{order}/invoice', Admin\OrderInvoiceController::class)->name('orders.invoice');
        });
        Route::middleware(Authorize::using(Permission::OrdersManage))->group(function () {
            Route::get('orders/{order}/edit', [Admin\OrderController::class, 'edit'])->name('orders.edit');
            Route::patch('orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
            Route::post('orders/{order}/activities', [Admin\OrderActivityController::class, 'store'])->name('orders.activities.store');
            Route::patch('orders/{order}/activities/{activity}', [Admin\OrderActivityController::class, 'update'])->name('orders.activities.update');
            Route::delete('orders/{order}/activities/{activity}', [Admin\OrderActivityController::class, 'destroy'])->name('orders.activities.destroy');
            Route::post('orders/{order}/resend-notification', Admin\ResendOrderNotificationController::class)->name('orders.resend-notification');
            Route::post('orders/{order}/record-payment', [Admin\OrderPaymentRecordController::class, 'store'])->name('orders.record-payment.store');
            Route::post('orders/{order}/void-payment', Admin\VoidPaymentController::class)->name('orders.void-payment');
            Route::post('orders/{order}/refund-credit', [Admin\OrderRefundCreditController::class, 'store'])->name('orders.refund-credit.store');
            Route::post('orders/{order}/hold', Admin\HoldOrderController::class)->name('orders.hold');
            Route::post('orders/{order}/release-hold', Admin\ReleaseOrderHoldController::class)->name('orders.release-hold');
            Route::post('orders/{order}/in-progress', Admin\InProgressOrderController::class)->name('orders.in-progress');
        });
        Route::middleware(Authorize::using(Permission::OrdersRefund))->group(function () {
            Route::get('orders/{order}/refund', [Admin\OrderRefundController::class, 'create'])->name('orders.refund');
            Route::post('orders/{order}/refund', [Admin\OrderRefundController::class, 'store']);
        });
        Route::post('orders/{order}/cancel', Admin\CancelOrderController::class)->middleware(Authorize::using(Permission::OrdersCancel))->name('orders.cancel');
        Route::middleware(Authorize::using(Permission::OrdersFulfill))->scopeBindings()->group(function () {
            Route::post('orders/{order}/shipments', [Admin\OrderShipmentController::class, 'store'])->name('orders.shipments.store');
            Route::patch('orders/{order}/shipments/{shipment}', [Admin\OrderShipmentController::class, 'update'])->name('orders.shipments.update');
            Route::delete('orders/{order}/shipments/{shipment}', [Admin\OrderShipmentController::class, 'destroy'])->name('orders.shipments.destroy');
        });
        // products
        Route::get('products/search', Admin\ProductSearchController::class)->middleware(Authorize::using(Permission::ProductsReference))->name('products.search');
        Route::middleware(Authorize::using(Permission::ProductsView))->group(function () {
            Route::get('products', [Admin\ProductController::class, 'index'])->name('products.index');
        });
        Route::middleware(Authorize::using(Permission::ProductsManage))->group(function () {
            Route::get('products/create', [Admin\ProductController::class, 'create'])->name('products.create');
            Route::post('products', [Admin\ProductController::class, 'store'])->name('products.store');
            Route::post('products/{product}/duplicate', [Admin\DuplicateProductController::class, 'store'])->name('products.duplicate');
            Route::get('products/{product}/edit', [Admin\ProductController::class, 'edit'])->name('products.edit');
            Route::patch('products/{product}', [Admin\ProductController::class, 'update'])->name('products.update');
        });
        Route::delete('products/bulk', [Admin\BulkProductController::class, 'destroy'])->middleware(Authorize::using(Permission::ProductsDelete))->name('products.bulk.destroy');
        // inventory
        Route::middleware(Authorize::using(Permission::InventoryView))->group(function () {
            Route::get('inventory', [Admin\InventoryController::class, 'index'])->name('inventory.index');
            Route::get('inventory/{product}', [Admin\InventoryController::class, 'show'])->name('inventory.show');
        });
        Route::post('inventory/stock-adjustments', [Admin\StockAdjustmentController::class, 'store'])->middleware(Authorize::using(Permission::InventoryManage))->name('inventory.stock-adjustments.store');
        // categories
        Route::get('categories/search', Admin\CategorySearchController::class)->middleware(Authorize::using(Permission::CategoriesReference))->name('categories.search');
        Route::middleware(Authorize::using(Permission::CategoriesView))->group(function () {
            Route::get('categories', [Admin\CategoryController::class, 'index'])->name('categories.index');
        });
        Route::post('categories', [Admin\CategoryController::class, 'store'])->middleware(Authorize::using(Permission::CategoriesManage))->name('categories.store');
        Route::middleware(Authorize::using(Permission::CategoriesManage))->group(function () {
            Route::patch('categories/{category}/reorder', [Admin\CategoryReorderController::class, 'update'])->name('categories.reorder');
            Route::patch('categories/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
        });
        Route::delete('categories/{category}', [Admin\CategoryController::class, 'destroy'])->middleware(Authorize::using(Permission::CategoriesDelete))->name('categories.destroy');
        // brands
        Route::get('brands/search', Admin\BrandSearchController::class)->middleware(Authorize::using(Permission::BrandsReference))->name('brands.search');
        Route::middleware(Authorize::using(Permission::BrandsView))->group(function () {
            Route::get('brands', [Admin\BrandController::class, 'index'])->name('brands.index');
        });
        Route::post('brands', [Admin\BrandController::class, 'store'])->middleware(Authorize::using(Permission::BrandsManage))->name('brands.store');
        Route::patch('brands/{brand}', [Admin\BrandController::class, 'update'])->middleware(Authorize::using(Permission::BrandsManage))->name('brands.update');
        Route::delete('brands/bulk', [Admin\BulkBrandController::class, 'destroy'])->middleware(Authorize::using(Permission::BrandsDelete))->name('brands.bulk.destroy');
        // search synonyms
        // blog posts
        // coupons
        Route::post('coupons/validate', Admin\CouponValidationController::class)->middleware(Authorize::using(Permission::OrdersManage))->name('coupons.validate');
        Route::get('coupons', [Admin\CouponController::class, 'index'])->middleware(Authorize::using(Permission::CouponsView))->name('coupons.index');
        Route::middleware(Authorize::using(Permission::CouponsManage))->group(function () {
            Route::get('coupons/create', [Admin\CouponController::class, 'create'])->name('coupons.create');
            Route::post('coupons', [Admin\CouponController::class, 'store'])->name('coupons.store');
            Route::get('coupons/{coupon}/edit', [Admin\CouponController::class, 'edit'])->name('coupons.edit');
            Route::patch('coupons/{coupon}', [Admin\CouponController::class, 'update'])->name('coupons.update');
        });
        Route::delete('coupons/bulk', [Admin\BulkCouponController::class, 'destroy'])->middleware(Authorize::using(Permission::CouponsDelete))->name('coupons.bulk.destroy');
        // flash sales
        // customers
        Route::get('users/search', Admin\UserSearchController::class)->middleware(Authorize::using(Permission::UsersReference))->name('users.search');
        Route::get('customers', [Admin\CustomerController::class, 'index'])->middleware(Authorize::using(Permission::CustomersView))->name('customers.index');
        Route::middleware(Authorize::using(Permission::CustomersManage))->group(function () {
            Route::get('customers/create', [Admin\CustomerController::class, 'create'])->name('customers.create');
            Route::post('customers', [Admin\CustomerController::class, 'store'])->name('customers.store');
            Route::get('customers/{customer}/edit', [Admin\CustomerController::class, 'edit'])->name('customers.edit');
            Route::patch('customers/{customer}', [Admin\CustomerController::class, 'update'])->name('customers.update');
            Route::post('customers/{customer}/addresses', [Admin\CustomerAddressController::class, 'store'])->name('customers.addresses.store');
            Route::patch('customers/{customer}/addresses/{address}', [Admin\CustomerAddressController::class, 'update'])->name('customers.addresses.update');
            Route::delete('customers/{customer}/addresses/{address}', [Admin\CustomerAddressController::class, 'destroy'])->name('customers.addresses.destroy');
            Route::post('customers/{customer}/addresses/{address}/default', Admin\SetDefaultCustomerAddressController::class)->name('customers.addresses.default');
        });
        Route::delete('customers/bulk', [Admin\BulkCustomerController::class, 'destroy'])->middleware(Authorize::using(Permission::CustomersDelete))->name('customers.bulk.destroy');
        // reviews
        Route::get('reviews', [Admin\ReviewController::class, 'index'])->middleware(Authorize::using(Permission::ReviewsView))->name('reviews.index');
        Route::middleware(Authorize::using(Permission::ReviewsManage))->group(function () {
            Route::post('reviews', [Admin\ReviewController::class, 'store'])->name('reviews.store');
            Route::patch('reviews/{review}', [Admin\ReviewController::class, 'update'])->name('reviews.update');
            Route::post('reviews/approve', [Admin\ReviewApproveController::class, 'store'])->name('reviews.approve');
            Route::post('reviews/reject', [Admin\ReviewRejectController::class, 'store'])->name('reviews.reject');
        });
        Route::delete('reviews/bulk', [Admin\BulkReviewController::class, 'destroy'])->middleware(Authorize::using(Permission::ReviewsDelete))->name('reviews.bulk.destroy');
        // users
        // roles
        // regions
        Route::get('regions/search', Admin\RegionSearchController::class)->middleware(Authorize::using(Permission::RegionsReference))->name('regions.search');
        Route::middleware(Authorize::using(Permission::RegionsView))->group(function () {
            Route::get('regions', [Admin\RegionController::class, 'index'])->name('regions.index');
        });
        Route::post('regions', [Admin\RegionController::class, 'store'])->middleware(Authorize::using(Permission::RegionsManage))->name('regions.store');
        Route::patch('regions/{region}', [Admin\RegionController::class, 'update'])->middleware(Authorize::using(Permission::RegionsManage))->name('regions.update');
        Route::delete('regions/bulk', [Admin\BulkRegionController::class, 'destroy'])->middleware(Authorize::using(Permission::RegionsDelete))->name('regions.bulk.destroy');
        // settings
        Route::get('settings', [Admin\SettingController::class, 'index'])->middleware(Authorize::using('settings.access'))->name('settings.index');
        Route::get('settings/general', [Admin\GeneralSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsGeneralConfigure))->name('settings.general.show');
        Route::patch('settings/general', [Admin\GeneralSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsGeneralConfigure))->name('settings.general.update');
        Route::get('settings/store', [Admin\StoreSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsStoreConfigure))->name('settings.store.show');
        Route::patch('settings/store', [Admin\StoreSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsStoreConfigure))->name('settings.store.update');
        Route::get('settings/language', [Admin\LanguageSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsLanguageConfigure))->name('settings.language.show');
        Route::patch('settings/language', [Admin\LanguageSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsLanguageConfigure))->name('settings.language.update');
        Route::get('settings/currency', [Admin\CurrencySettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsCurrencyConfigure))->name('settings.currency.show');
        Route::patch('settings/currency', [Admin\CurrencySettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsCurrencyConfigure))->name('settings.currency.update');
        Route::get('settings/shipping', [Admin\ShippingSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsShippingConfigure))->name('settings.shipping.show');
        Route::patch('settings/shipping', [Admin\ShippingSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsShippingConfigure))->name('settings.shipping.update');
        Route::get('settings/tax', [Admin\TaxSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsTaxConfigure))->name('settings.tax.show');
        Route::patch('settings/tax', [Admin\TaxSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsTaxConfigure))->name('settings.tax.update');
        Route::get('settings/checkout', [Admin\CheckoutSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsCheckoutConfigure))->name('settings.checkout.show');
        Route::patch('settings/checkout', [Admin\CheckoutSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsCheckoutConfigure))->name('settings.checkout.update');
        Route::get('settings/payment', [Admin\PaymentSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsPaymentConfigure))->name('settings.payment.show');
        Route::get('settings/newsletter', [Admin\NewsletterSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsNewsletterConfigure))->name('settings.newsletter.show');
        Route::patch('settings/newsletter', [Admin\NewsletterSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsNewsletterConfigure))->name('settings.newsletter.update');
        Route::get('settings/mail', [Admin\MailSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsMailConfigure))->name('settings.mail.show');
        Route::patch('settings/mail', [Admin\MailSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsMailConfigure))->name('settings.mail.update');
        Route::post('settings/mail/test', Admin\SendTestMailController::class)->middleware(Authorize::using(Permission::SettingsMailConfigure))->name('settings.mail.test');
        Route::get('settings/notification', [Admin\NotificationSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsNotificationConfigure))->name('settings.notification.show');
        Route::patch('settings/notification', [Admin\NotificationSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsNotificationConfigure))->name('settings.notification.update');
        Route::get('settings/policy', [Admin\PolicySettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsPolicyConfigure))->name('settings.policy.show');
        Route::patch('settings/policy', [Admin\PolicySettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsPolicyConfigure))->name('settings.policy.update');
        Route::get('settings/seo', [Admin\SeoSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsSeoConfigure))->name('settings.seo.show');
        Route::patch('settings/seo', [Admin\SeoSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsSeoConfigure))->name('settings.seo.update');
        Route::get('settings/integration', [Admin\IntegrationSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsIntegrationConfigure))->name('settings.integration.show');
        Route::patch('settings/integration', [Admin\IntegrationSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsIntegrationConfigure))->name('settings.integration.update');
        Route::get('settings/system', [Admin\SystemSettingController::class, 'show'])->middleware(Authorize::using(Permission::SettingsSystemConfigure))->name('settings.system.show');
        Route::patch('settings/system', [Admin\SystemSettingController::class, 'update'])->middleware(Authorize::using(Permission::SettingsSystemConfigure))->name('settings.system.update');
        Route::post('settings/system/cache', Admin\RebuildCacheController::class)->middleware(Authorize::using(Permission::SettingsSystemConfigure))->name('settings.system.cache.rebuild');
        // shipping (carriers, rates, packages)
        Route::middleware(Authorize::using(Permission::SettingsShippingConfigure))->group(function () {
            Route::post('shipping/carriers', [Admin\ShippingCarrierController::class, 'store'])->name('shipping.carriers.store');
            Route::patch('shipping/carriers/{carrier}', [Admin\ShippingCarrierController::class, 'update'])->name('shipping.carriers.update');
            Route::delete('shipping/carriers/{carrier}', [Admin\ShippingCarrierController::class, 'destroy'])->name('shipping.carriers.destroy');
            Route::post('shipping/rates', [Admin\ShippingRateController::class, 'store'])->name('shipping.rates.store');
            Route::patch('shipping/rates/{shippingRate}', [Admin\ShippingRateController::class, 'update'])->name('shipping.rates.update');
            Route::delete('shipping/rates/{shippingRate}', [Admin\ShippingRateController::class, 'destroy'])->name('shipping.rates.destroy');
        });
        // tax
        Route::middleware(Authorize::using(Permission::SettingsTaxConfigure))->group(function () {
            Route::post('tax/rates', [Admin\TaxRateController::class, 'store'])->name('tax.rates.store');
            Route::patch('tax/rates/{taxRate}', [Admin\TaxRateController::class, 'update'])->name('tax.rates.update');
            Route::delete('tax/rates/{taxRate}', [Admin\TaxRateController::class, 'destroy'])->name('tax.rates.destroy');
        });
        // currencies
        // payment
        Route::middleware(Authorize::using(Permission::SettingsPaymentConfigure))->group(function () {
            Route::post('payment/gateways', [Admin\PaymentGatewayController::class, 'store'])->name('payment.gateways.store');
            Route::patch('payment/gateways/{gateway}', [Admin\PaymentGatewayController::class, 'update'])->name('payment.gateways.update');
            Route::post('payment/gateways/test', Admin\TestPaymentGatewayConnectionController::class)->name('payment.gateways.test');
        });
        // newsletter
        // storefront
        Route::middleware(Authorize::using(Permission::StorefrontView))->group(function () {
            Route::get('storefront', [Admin\StorefrontController::class, 'index'])->name('storefront.builder.index');
            Route::get('storefront/homepage', [Admin\StorefrontHomepageController::class, 'edit'])->name('storefront.homepage.edit');
            Route::get('storefront/header', [Admin\StorefrontHeaderController::class, 'edit'])->name('storefront.header.edit');
            Route::get('storefront/footer', [Admin\StorefrontFooterController::class, 'edit'])->name('storefront.footer.edit');
            Route::get('storefront/announcements', [Admin\AnnouncementController::class, 'index'])->name('storefront.announcements.index');
            Route::get('storefront/product-list', [Admin\StorefrontProductListController::class, 'edit'])->name('storefront.product-list.edit');
            Route::get('storefront/product-detail', [Admin\StorefrontProductDetailController::class, 'edit'])->name('storefront.product-detail.edit');
            Route::get('storefront/theme', [Admin\StorefrontThemeController::class, 'edit'])->name('storefront.theme.edit');
            Route::get('storefront/custom-css', [Admin\StorefrontCustomCssController::class, 'edit'])->name('storefront.custom-css.edit');
            Route::get('storefront/custom-js', [Admin\StorefrontCustomJsController::class, 'edit'])->name('storefront.custom-js.edit');
        });
        Route::middleware(Authorize::using(Permission::StorefrontUpdate))->group(function () {
            Route::get('storefront/homepage/sections/create', [Admin\StorefrontSectionController::class, 'create'])->name('storefront.homepage.sections.create');
            Route::post('storefront/homepage/sections', [Admin\StorefrontSectionController::class, 'store'])->name('storefront.homepage.sections.store');
            Route::get('storefront/homepage/sections/{section}/edit', [Admin\StorefrontSectionController::class, 'edit'])->name('storefront.homepage.sections.edit');
            Route::patch('storefront/homepage/sections/reorder', [Admin\StorefrontSectionReorderController::class, 'update'])->name('storefront.homepage.sections.reorder');
            Route::patch('storefront/homepage/sections/{section}', [Admin\StorefrontSectionController::class, 'update'])->name('storefront.homepage.sections.update');
            Route::delete('storefront/homepage/sections/{section}', [Admin\StorefrontSectionController::class, 'destroy'])->name('storefront.homepage.sections.destroy');
            Route::patch('storefront/header', [Admin\StorefrontHeaderController::class, 'update'])->name('storefront.header.update');
            Route::patch('storefront/footer', [Admin\StorefrontFooterController::class, 'update'])->name('storefront.footer.update');
            Route::get('storefront/menu-items/create', [Admin\MenuItemController::class, 'create'])->name('storefront.menu-items.create');
            Route::post('storefront/menu-items', [Admin\MenuItemController::class, 'store'])->name('storefront.menu-items.store');
            Route::get('storefront/menu-items/{menuItem}/edit', [Admin\MenuItemController::class, 'edit'])->name('storefront.menu-items.edit');
            Route::patch('storefront/menu-items/{menuItem}/reorder', [Admin\MenuItemReorderController::class, 'update'])->name('storefront.menu-items.reorder');
            Route::patch('storefront/menu-items/{menuItem}', [Admin\MenuItemController::class, 'update'])->name('storefront.menu-items.update');
            Route::delete('storefront/menu-items/{menuItem}', [Admin\MenuItemController::class, 'destroy'])->name('storefront.menu-items.destroy');
            Route::get('storefront/announcements/create', [Admin\AnnouncementController::class, 'create'])->name('storefront.announcements.create');
            Route::post('storefront/announcements', [Admin\AnnouncementController::class, 'store'])->name('storefront.announcements.store');
            Route::patch('storefront/announcements/reorder', Admin\AnnouncementReorderController::class)->name('storefront.announcements.reorder');
            Route::get('storefront/announcements/{announcement}/edit', [Admin\AnnouncementController::class, 'edit'])->name('storefront.announcements.edit');
            Route::patch('storefront/announcements/{announcement}', [Admin\AnnouncementController::class, 'update'])->name('storefront.announcements.update');
            Route::delete('storefront/announcements/{announcement}', [Admin\AnnouncementController::class, 'destroy'])->name('storefront.announcements.destroy');
            Route::patch('storefront/product-list', [Admin\StorefrontProductListController::class, 'update'])->name('storefront.product-list.update');
            Route::patch('storefront/product-detail', [Admin\StorefrontProductDetailController::class, 'update'])->name('storefront.product-detail.update');
            Route::patch('storefront/theme', [Admin\StorefrontThemeController::class, 'update'])->name('storefront.theme.update');
            Route::patch('storefront/custom-css', [Admin\StorefrontCustomCssController::class, 'update'])->name('storefront.custom-css.update');
            Route::patch('storefront/custom-js', [Admin\StorefrontCustomJsController::class, 'update'])->name('storefront.custom-js.update');
        });
    });
});
