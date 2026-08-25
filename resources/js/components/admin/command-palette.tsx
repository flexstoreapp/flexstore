import { router } from '@inertiajs/react';
import {
    BarChart3Icon,
    BellIcon,
    BlocksIcon,
    ClipboardListIcon,
    DollarSignIcon,
    GlobeIcon,
    KeyIcon,
    LayoutDashboardIcon,
    ListTreeIcon,
    MailIcon,
    MapPinIcon,
    MonitorIcon,
    NewspaperIcon,
    PackageIcon,
    PercentIcon,
    PlusIcon,
    SearchIcon,
    SendIcon,
    Settings2Icon,
    ShieldCheckIcon,
    ShieldIcon,
    ShoppingBagIcon,
    ShoppingCartIcon,
    StarIcon,
    StoreIcon,
    TagIcon,
    TicketIcon,
    TruckIcon,
    UserCog2Icon,
    UserIcon,
    UserRoundIcon,
    WalletIcon,
    ZapIcon,
} from 'lucide-react';
import { useState } from 'react';

import * as BrandController from '@/actions/App/Http/Controllers/Admin/BrandController';
import * as CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import * as CheckoutSettingController from '@/actions/App/Http/Controllers/Admin/CheckoutSettingController';
import * as CouponController from '@/actions/App/Http/Controllers/Admin/CouponController';
import * as CurrencySettingController from '@/actions/App/Http/Controllers/Admin/CurrencySettingController';
import * as CustomerController from '@/actions/App/Http/Controllers/Admin/CustomerController';
import * as DashboardController from '@/actions/App/Http/Controllers/Admin/DashboardController';
import * as GeneralSettingController from '@/actions/App/Http/Controllers/Admin/GeneralSettingController';
import * as IntegrationSettingController from '@/actions/App/Http/Controllers/Admin/IntegrationSettingController';
import * as InventoryController from '@/actions/App/Http/Controllers/Admin/InventoryController';
import * as LanguageSettingController from '@/actions/App/Http/Controllers/Admin/LanguageSettingController';
import * as MailSettingController from '@/actions/App/Http/Controllers/Admin/MailSettingController';
import * as NewsletterSettingController from '@/actions/App/Http/Controllers/Admin/NewsletterSettingController';
import * as NotificationSettingController from '@/actions/App/Http/Controllers/Admin/NotificationSettingController';
import * as OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import * as PasswordController from '@/actions/App/Http/Controllers/Admin/PasswordController';
import * as PaymentSettingController from '@/actions/App/Http/Controllers/Admin/PaymentSettingController';
import * as PolicySettingController from '@/actions/App/Http/Controllers/Admin/PolicySettingController';
import * as ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import * as ProfileController from '@/actions/App/Http/Controllers/Admin/ProfileController';
import * as RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import * as ReviewController from '@/actions/App/Http/Controllers/Admin/ReviewController';
import * as SecurityController from '@/actions/App/Http/Controllers/Admin/SecurityController';
import * as SeoSettingController from '@/actions/App/Http/Controllers/Admin/SeoSettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import * as ShippingSettingController from '@/actions/App/Http/Controllers/Admin/ShippingSettingController';
import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import * as StoreSettingController from '@/actions/App/Http/Controllers/Admin/StoreSettingController';
import * as SystemSettingController from '@/actions/App/Http/Controllers/Admin/SystemSettingController';
import * as TaxSettingController from '@/actions/App/Http/Controllers/Admin/TaxSettingController';
import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { Button } from '@/components/ui/button';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { useHotkey } from '@/hooks/admin/use-hotkey';
import { useOsDetector } from '@/hooks/admin/use-os-detector';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { __ } from '@/lib/i18n';
import { Permission, settingsPermissions } from '@/lib/permissions';
import type { NavItem } from '@/types';

export const pages: NavItem[] = [
    {
        title: __('Dashboard'),
        href: DashboardController.index(),
        icon: LayoutDashboardIcon,
        permission: Permission.DashboardView,
    },
    {
        title: __('Orders'),
        href: OrderController.index(),
        icon: ShoppingBagIcon,
        permission: Permission.OrdersView,
    },
    {
        title: __('Abandoned checkouts'),
        icon: ShoppingCartIcon,
        pro: true,
    },
    {
        title: __('Products'),
        href: ProductController.index(),
        icon: PackageIcon,
        permission: Permission.ProductsView,
    },
    {
        title: __('Inventory'),
        href: InventoryController.index(),
        icon: ClipboardListIcon,
        permission: Permission.InventoryView,
    },
    {
        title: __('Search synonyms'),
        icon: SearchIcon,
        pro: true,
    },
    {
        title: __('Categories'),
        href: CategoryController.index(),
        icon: ListTreeIcon,
        permission: Permission.CategoriesView,
    },
    {
        title: __('Brands'),
        href: BrandController.index(),
        icon: TagIcon,
        permission: Permission.BrandsView,
    },
    {
        title: __('Blog posts'),
        icon: NewspaperIcon,
        pro: true,
    },
    {
        title: __('Coupons'),
        href: CouponController.index(),
        icon: TicketIcon,
        permission: Permission.CouponsView,
    },
    {
        title: __('Flash sales'),
        icon: ZapIcon,
        pro: true,
    },
    {
        title: __('Customers'),
        href: CustomerController.index(),
        icon: UserRoundIcon,
        permission: Permission.CustomersView,
    },
    {
        title: __('Reviews'),
        href: ReviewController.index(),
        icon: StarIcon,
        permission: Permission.ReviewsView,
    },
    {
        title: __('Reports'),
        icon: BarChart3Icon,
        pro: true,
    },
    {
        title: __('Staff'),
        icon: UserCog2Icon,
        pro: true,
    },
    {
        title: __('Roles'),
        icon: ShieldIcon,
        pro: true,
    },
    {
        title: __('Regions'),
        href: RegionController.index(),
        icon: MapPinIcon,
        permission: Permission.RegionsView,
    },
    {
        title: __('Storefront'),
        href: StorefrontController.index(),
        icon: StoreIcon,
        permission: Permission.StorefrontView,
    },
    {
        title: __('Settings'),
        href: SettingController.index(),
        icon: Settings2Icon,
        permissions: settingsPermissions,
    },
];

const subPages: Record<string, NavItem[]> = {
    [__('Products')]: [
        {
            title: __('Add product'),
            href: ProductController.create(),
            icon: PlusIcon,
            permission: Permission.ProductsManage,
        },
    ],
    [__('Orders')]: [
        {
            title: __('Add order'),
            href: OrderController.create(),
            icon: PlusIcon,
            permission: Permission.OrdersManage,
        },
    ],
    [__('Customers')]: [
        {
            title: __('Add customer'),
            href: CustomerController.create(),
            icon: PlusIcon,
            permission: Permission.CustomersManage,
        },
    ],
    [__('Settings')]: [
        {
            title: __('General'),
            href: GeneralSettingController.show(),
            icon: Settings2Icon,
            permission: Permission.SettingsGeneralConfigure,
        },
        {
            title: __('Store'),
            href: StoreSettingController.show(),
            icon: StoreIcon,
            permission: Permission.SettingsStoreConfigure,
        },
        {
            title: __('Language'),
            href: LanguageSettingController.show(),
            icon: GlobeIcon,
            permission: Permission.SettingsLanguageConfigure,
        },
        {
            title: __('Currency'),
            href: CurrencySettingController.show(),
            icon: DollarSignIcon,
            permission: Permission.SettingsCurrencyConfigure,
        },
        {
            title: __('Shipping'),
            href: ShippingSettingController.show(),
            icon: TruckIcon,
            permission: Permission.SettingsShippingConfigure,
        },
        {
            title: __('Tax'),
            href: TaxSettingController.show(),
            icon: PercentIcon,
            permission: Permission.SettingsTaxConfigure,
        },
        {
            title: __('Payment'),
            href: PaymentSettingController.show(),
            icon: WalletIcon,
            permission: Permission.SettingsPaymentConfigure,
        },
        {
            title: __('Checkout'),
            href: CheckoutSettingController.show(),
            icon: ShoppingCartIcon,
            permission: Permission.SettingsCheckoutConfigure,
        },
        {
            title: __('Newsletter'),
            href: NewsletterSettingController.show(),
            icon: MailIcon,
            permission: Permission.SettingsNewsletterConfigure,
        },
        {
            title: __('Mail'),
            href: MailSettingController.show(),
            icon: SendIcon,
            permission: Permission.SettingsMailConfigure,
        },
        {
            title: __('Notification'),
            href: NotificationSettingController.show(),
            icon: BellIcon,
            permission: Permission.SettingsNotificationConfigure,
        },
        {
            title: __('Policy'),
            href: PolicySettingController.show(),
            icon: ShieldCheckIcon,
            permission: Permission.SettingsPolicyConfigure,
        },
        {
            title: __('SEO'),
            href: SeoSettingController.show(),
            icon: SearchIcon,
            permission: Permission.SettingsSeoConfigure,
        },
        {
            title: __('Integration'),
            href: IntegrationSettingController.show(),
            icon: BlocksIcon,
            permission: Permission.SettingsIntegrationConfigure,
        },
        {
            title: __('System'),
            href: SystemSettingController.show(),
            icon: MonitorIcon,
            permission: Permission.SettingsSystemConfigure,
        },
    ],
    [__('Account')]: [
        {
            title: __('Profile'),
            href: ProfileController.edit(),
            icon: UserIcon,
        },
        {
            title: __('Password'),
            href: PasswordController.edit(),
            icon: KeyIcon,
        },
        {
            title: __('Security'),
            href: SecurityController.show(),
            icon: ShieldCheckIcon,
        },
    ],
};

export function CommandPalette() {
    const [open, setOpen] = useState(false);
    const isMacOs = useOsDetector() === 'macOS';
    const { hasPermission, hasAnyPermission } = usePermissions();
    const { open: openProUpgrade } = useProUpgrade();

    useHotkey(isMacOs ? 'cmd+k' : 'ctrl+k', () => setOpen((open) => !open));

    const handleSelect = (item: NavItem) => {
        setOpen(false);

        if (item.pro || !item.href) {
            openProUpgrade(item.title);

            return;
        }

        router.visit(item.href);
    };

    const isVisible = (item: NavItem) => {
        if (item.pro) return true;
        if (item.permissions) return hasAnyPermission(item.permissions);
        if (item.permission) return hasPermission(item.permission);
        return true;
    };

    const visiblePages = pages.filter(isVisible);

    const visibleSubPages = Object.entries(subPages).reduce(
        (acc, [title, items]) => {
            const filteredItems = items.filter(isVisible);

            if (filteredItems.length > 0) {
                acc[title] = filteredItems;
            }

            return acc;
        },
        {} as Record<string, NavItem[]>,
    );

    return (
        <>
            <Button
                variant="ghost"
                size="icon-md"
                className="md:hidden"
                onClick={() => setOpen(true)}
                aria-label={__('Search')}
            >
                <SearchIcon />
            </Button>

            <Button
                variant="outline"
                className="hidden gap-1 rounded-full bg-muted/50 shadow-none md:flex"
                onClick={() => setOpen(true)}
            >
                <SearchIcon />

                <kbd className="hidden font-sans text-xs md:block">
                    <span>{isMacOs ? '⌘' : 'Ctrl+'}</span>
                    <span>K</span>
                </kbd>
            </Button>

            <CommandDialog open={open} onOpenChange={setOpen}>
                <CommandInput placeholder={__('Search...')} />
                <CommandList>
                    <CommandEmpty>{__('No items found.')}</CommandEmpty>
                    <CommandGroup heading={__('Pages')}>
                        {visiblePages.map((item) => (
                            <CommandItem
                                key={item.title}
                                onSelect={() => handleSelect(item)}
                                className="flex items-center"
                            >
                                {item.icon && <item.icon />}
                                <p className="truncate text-sm font-medium">{item.title}</p>
                                {item.pro && <ProBadge className="ms-auto shrink-0" />}
                            </CommandItem>
                        ))}
                    </CommandGroup>

                    {Object.entries(visibleSubPages).map(([title, items]) => (
                        <CommandGroup key={title} heading={title}>
                            {items.map((item) => (
                                <CommandItem
                                    key={item.title}
                                    onSelect={() => handleSelect(item)}
                                    className="flex items-center"
                                >
                                    {item.icon && <item.icon />}
                                    <p className="text-sm font-medium">{item.title}</p>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    ))}
                </CommandList>
            </CommandDialog>
        </>
    );
}
