<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class() extends Migration
{
    public function up(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->freePermissions() as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission]);
        }

        Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin]);

        Role::query()->firstOrCreate(['name' => RoleEnum::Admin])->syncPermissions($this->freePermissions());

        Role::query()->firstOrCreate(['name' => RoleEnum::Customer]);
    }

    /**
     * @return list<PermissionEnum>
     */
    private function freePermissions(): array
    {
        return [
            PermissionEnum::DashboardView,

            PermissionEnum::OrdersView,
            PermissionEnum::OrdersManage,
            PermissionEnum::OrdersRefund,
            PermissionEnum::OrdersFulfill,
            PermissionEnum::OrdersCancel,

            PermissionEnum::ProductsView,
            PermissionEnum::ProductsManage,
            PermissionEnum::ProductsDelete,
            PermissionEnum::ProductsReference,

            PermissionEnum::InventoryView,
            PermissionEnum::InventoryManage,

            PermissionEnum::CategoriesView,
            PermissionEnum::CategoriesManage,
            PermissionEnum::CategoriesDelete,
            PermissionEnum::CategoriesReference,

            PermissionEnum::BrandsView,
            PermissionEnum::BrandsManage,
            PermissionEnum::BrandsDelete,
            PermissionEnum::BrandsReference,

            PermissionEnum::CouponsView,
            PermissionEnum::CouponsManage,
            PermissionEnum::CouponsDelete,

            PermissionEnum::UsersReference,

            PermissionEnum::CustomersView,
            PermissionEnum::CustomersManage,
            PermissionEnum::CustomersDelete,

            PermissionEnum::ReviewsView,
            PermissionEnum::ReviewsManage,
            PermissionEnum::ReviewsDelete,

            PermissionEnum::RegionsView,
            PermissionEnum::RegionsManage,
            PermissionEnum::RegionsDelete,
            PermissionEnum::RegionsReference,

            PermissionEnum::SettingsGeneralConfigure,
            PermissionEnum::SettingsStoreConfigure,
            PermissionEnum::SettingsLanguageConfigure,
            PermissionEnum::SettingsCurrencyConfigure,
            PermissionEnum::SettingsShippingConfigure,
            PermissionEnum::SettingsTaxConfigure,
            PermissionEnum::SettingsPaymentConfigure,
            PermissionEnum::SettingsCheckoutConfigure,
            PermissionEnum::SettingsNewsletterConfigure,
            PermissionEnum::SettingsMailConfigure,
            PermissionEnum::SettingsNotificationConfigure,
            PermissionEnum::SettingsPolicyConfigure,
            PermissionEnum::SettingsSeoConfigure,
            PermissionEnum::SettingsIntegrationConfigure,
            PermissionEnum::SettingsSystemConfigure,

            PermissionEnum::StorefrontView,
            PermissionEnum::StorefrontUpdate,

            PermissionEnum::MediaUpload,
        ];
    }
};
