<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

enum Permission: string
{
    case DashboardView = 'dashboard.view';

    case OrdersView = 'orders.view';
    case OrdersManage = 'orders.manage';
    case OrdersRefund = 'orders.refund';
    case OrdersFulfill = 'orders.fulfill';
    case OrdersCancel = 'orders.cancel';

    case ProductsView = 'products.view';
    case ProductsManage = 'products.manage';
    case ProductsDelete = 'products.delete';
    case ProductsReference = 'products.reference';

    case InventoryView = 'inventory.view';
    case InventoryManage = 'inventory.manage';

    case CategoriesView = 'categories.view';
    case CategoriesManage = 'categories.manage';
    case CategoriesDelete = 'categories.delete';
    case CategoriesReference = 'categories.reference';

    case BrandsView = 'brands.view';
    case BrandsManage = 'brands.manage';
    case BrandsDelete = 'brands.delete';
    case BrandsReference = 'brands.reference';

    case CouponsView = 'coupons.view';
    case CouponsManage = 'coupons.manage';
    case CouponsDelete = 'coupons.delete';

    case UsersReference = 'users.reference';

    case CustomersView = 'customers.view';
    case CustomersManage = 'customers.manage';
    case CustomersDelete = 'customers.delete';

    case ReviewsView = 'reviews.view';
    case ReviewsManage = 'reviews.manage';
    case ReviewsDelete = 'reviews.delete';

    case RegionsView = 'regions.view';
    case RegionsManage = 'regions.manage';
    case RegionsDelete = 'regions.delete';
    case RegionsReference = 'regions.reference';

    case SettingsGeneralConfigure = 'settings.general.configure';
    case SettingsStoreConfigure = 'settings.store.configure';
    case SettingsLanguageConfigure = 'settings.language.configure';
    case SettingsCurrencyConfigure = 'settings.currency.configure';
    case SettingsShippingConfigure = 'settings.shipping.configure';
    case SettingsTaxConfigure = 'settings.tax.configure';
    case SettingsPaymentConfigure = 'settings.payment.configure';
    case SettingsCheckoutConfigure = 'settings.checkout.configure';
    case SettingsNewsletterConfigure = 'settings.newsletter.configure';
    case SettingsMailConfigure = 'settings.mail.configure';
    case SettingsNotificationConfigure = 'settings.notification.configure';
    case SettingsPolicyConfigure = 'settings.policy.configure';
    case SettingsSeoConfigure = 'settings.seo.configure';
    case SettingsIntegrationConfigure = 'settings.integration.configure';
    case SettingsSystemConfigure = 'settings.system.configure';

    case StorefrontView = 'storefront.view';
    case StorefrontUpdate = 'storefront.update';

    case MediaUpload = 'media.upload';

    /**
     * @return list<self>
     */
    public static function settings(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $permission): bool => str_starts_with($permission->value, 'settings.'),
        ));
    }

    public function kind(): PermissionKind
    {
        return match (Str::afterLast($this->value, '.')) {
            'reference', 'upload' => PermissionKind::Reference,
            'view' => PermissionKind::View,
            'delete' => PermissionKind::Destructive,
            default => PermissionKind::Manage,
        };
    }

    public function isAssignable(): bool
    {
        return $this->kind() !== PermissionKind::Reference;
    }

    /**
     * @return list<self>
     */
    public function implies(): array
    {
        return match ($this) {
            self::RegionsView => [self::RegionsReference],
            self::ProductsView => [self::ProductsReference],
            self::CategoriesView => [self::CategoriesReference],
            self::BrandsView => [self::BrandsReference],

            self::SettingsTaxConfigure => [self::RegionsReference],

            self::SettingsShippingConfigure => [
                self::RegionsReference,
                self::ProductsReference,
                self::CategoriesReference,
            ],

            self::SettingsPaymentConfigure => [
                self::RegionsReference,
                self::ProductsReference,
                self::CategoriesReference,
            ],

            self::ProductsManage => [
                self::CategoriesReference,
                self::BrandsReference,
                self::MediaUpload,
            ],

            self::OrdersManage => [
                self::UsersReference,
                self::ProductsReference,
            ],

            self::ReviewsManage => [
                self::ProductsReference,
                self::UsersReference,
            ],

            self::StorefrontUpdate => [
                self::BrandsReference,
                self::CategoriesReference,
                self::ProductsReference,
                self::MediaUpload,
            ],

            self::CategoriesManage,
            self::BrandsManage => [self::MediaUpload],

            default => [],
        };
    }
}
