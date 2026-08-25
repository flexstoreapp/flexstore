export const Role = {
    SuperAdmin: 'Super Admin',
} as const;

export const Permission = {
    DashboardView: 'dashboard.view',

    OrdersView: 'orders.view',
    OrdersManage: 'orders.manage',
    OrdersRefund: 'orders.refund',
    OrdersFulfill: 'orders.fulfill',
    OrdersCancel: 'orders.cancel',

    ReturnsView: 'returns.view',
    ReturnsManage: 'returns.manage',

    AbandonedCheckoutsView: 'abandoned-checkouts.view',
    AbandonedCheckoutsManage: 'abandoned-checkouts.manage',

    ProductsView: 'products.view',
    ProductsManage: 'products.manage',
    ProductsDelete: 'products.delete',
    ProductsReference: 'products.reference',

    InventoryView: 'inventory.view',
    InventoryManage: 'inventory.manage',

    CategoriesView: 'categories.view',
    CategoriesManage: 'categories.manage',
    CategoriesDelete: 'categories.delete',
    CategoriesReference: 'categories.reference',

    BrandsView: 'brands.view',
    BrandsManage: 'brands.manage',
    BrandsDelete: 'brands.delete',
    BrandsReference: 'brands.reference',

    SearchSynonymsView: 'search-synonyms.view',
    SearchSynonymsManage: 'search-synonyms.manage',
    SearchSynonymsDelete: 'search-synonyms.delete',

    BlogPostsView: 'blog-posts.view',
    BlogPostsManage: 'blog-posts.manage',
    BlogPostsDelete: 'blog-posts.delete',
    BlogPostsReference: 'blog-posts.reference',

    CouponsView: 'coupons.view',
    CouponsManage: 'coupons.manage',
    CouponsDelete: 'coupons.delete',

    FlashSalesView: 'flash-sales.view',
    FlashSalesManage: 'flash-sales.manage',
    FlashSalesDelete: 'flash-sales.delete',
    FlashSalesReference: 'flash-sales.reference',

    CustomersView: 'customers.view',
    CustomersManage: 'customers.manage',
    CustomersDelete: 'customers.delete',

    ReviewsView: 'reviews.view',
    ReviewsManage: 'reviews.manage',
    ReviewsDelete: 'reviews.delete',

    StaffView: 'staff.view',
    StaffManage: 'staff.manage',
    StaffDelete: 'staff.delete',
    UsersReference: 'users.reference',

    RolesView: 'roles.view',
    RolesManage: 'roles.manage',
    RolesDelete: 'roles.delete',

    RegionsView: 'regions.view',
    RegionsManage: 'regions.manage',
    RegionsDelete: 'regions.delete',
    RegionsReference: 'regions.reference',

    ReportsView: 'reports.view',

    StorefrontView: 'storefront.view',
    StorefrontUpdate: 'storefront.update',

    SettingsGeneralConfigure: 'settings.general.configure',
    SettingsStoreConfigure: 'settings.store.configure',
    SettingsLanguageConfigure: 'settings.language.configure',
    SettingsCurrencyConfigure: 'settings.currency.configure',
    SettingsShippingConfigure: 'settings.shipping.configure',
    SettingsTaxConfigure: 'settings.tax.configure',
    SettingsPaymentConfigure: 'settings.payment.configure',
    SettingsCheckoutConfigure: 'settings.checkout.configure',
    SettingsNewsletterConfigure: 'settings.newsletter.configure',
    SettingsMailConfigure: 'settings.mail.configure',
    SettingsNotificationConfigure: 'settings.notification.configure',
    SettingsPolicyConfigure: 'settings.policy.configure',
    SettingsSeoConfigure: 'settings.seo.configure',
    SettingsIntegrationConfigure: 'settings.integration.configure',
    SettingsSystemConfigure: 'settings.system.configure',

    MediaUpload: 'media.upload',
} as const;

export type Permission = (typeof Permission)[keyof typeof Permission];

export const settingsPermissions: Permission[] = Object.values(Permission).filter((permission) =>
    permission.startsWith('settings.'),
);
