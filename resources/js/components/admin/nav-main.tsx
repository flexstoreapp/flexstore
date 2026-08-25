import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3Icon,
    LayoutDashboardIcon,
    ListTreeIcon,
    NewspaperIcon,
    PackageIcon,
    SearchIcon,
    Settings2Icon,
    ShoppingBagIcon,
    ShoppingCartIcon,
    TicketIcon,
    UserRoundIcon,
    ClipboardListIcon,
    TagIcon,
    StarIcon,
    ShieldIcon,
    MapPinIcon,
    UserCog2Icon,
    StoreIcon,
    Undo2Icon,
    ZapIcon,
} from 'lucide-react';

import * as BrandController from '@/actions/App/Http/Controllers/Admin/BrandController';
import * as CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import * as CouponController from '@/actions/App/Http/Controllers/Admin/CouponController';
import * as CustomerController from '@/actions/App/Http/Controllers/Admin/CustomerController';
import * as DashboardController from '@/actions/App/Http/Controllers/Admin/DashboardController';
import * as InventoryController from '@/actions/App/Http/Controllers/Admin/InventoryController';
import * as OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import * as ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import * as RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import * as ReviewController from '@/actions/App/Http/Controllers/Admin/ReviewController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { __ } from '@/lib/i18n';
import { Permission, settingsPermissions } from '@/lib/permissions';
import type { NavItem } from '@/types';

function defaultNavItems(): NavItem[] {
    return [
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
            children: [
                { title: __('Returns'), icon: Undo2Icon, pro: true },
                { title: __('Abandoned checkouts'), icon: ShoppingCartIcon, pro: true },
            ],
        },
        {
            title: __('Products'),
            href: ProductController.index(),
            icon: PackageIcon,
            permission: Permission.ProductsView,
            children: [
                {
                    title: __('Inventory'),
                    href: InventoryController.index(),
                    icon: ClipboardListIcon,
                    permission: Permission.InventoryView,
                },
                { title: __('Search synonyms'), icon: SearchIcon, pro: true },
            ],
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
        { title: __('Blog posts'), icon: NewspaperIcon, pro: true },
        {
            title: __('Coupons'),
            href: CouponController.index(),
            icon: TicketIcon,
            permission: Permission.CouponsView,
        },
        { title: __('Flash sales'), icon: ZapIcon, pro: true },
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
        { title: __('Reports'), icon: BarChart3Icon, pro: true },
    ];
}

function systemNavItems(): NavItem[] {
    return [
        { title: __('Staff'), icon: UserCog2Icon, pro: true },
        { title: __('Roles'), icon: ShieldIcon, pro: true },
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
}

export function NavMain() {
    const page = usePage();
    const { hasPermission, hasAnyPermission } = usePermissions();
    const { open: openProUpgrade } = useProUpgrade();

    const currentPath = page.url.split('?')[0];

    const isActive = (itemUrl?: string) => {
        if (itemUrl === undefined) {
            return false;
        }

        if (itemUrl === DashboardController.index().url) {
            return currentPath === itemUrl;
        }

        return currentPath.startsWith(itemUrl);
    };

    const isVisible = (item: NavItem) => {
        if (item.pro) return true;
        if (item.permissions) return hasAnyPermission(item.permissions);
        if (item.permission) return hasPermission(item.permission);
        return true;
    };

    const visibleDefaultNavItems = defaultNavItems().filter(isVisible);
    const visibleSystemNavItems = systemNavItems().filter(isVisible);

    return (
        <>
            <SidebarGroup>
                <SidebarGroupContent>
                    <SidebarMenu>
                        {visibleDefaultNavItems.map((item) => {
                            const visibleChildren = item.children?.filter(isVisible) ?? [];
                            const childActive = visibleChildren.some((child) => isActive(child.href?.url));
                            const parentActive = isActive(item.href?.url);
                            const sectionActive = parentActive || childActive;

                            return (
                                <SidebarMenuItem key={item.title}>
                                    {item.pro || !item.href ? (
                                        <SidebarMenuButton
                                            tooltip={item.title}
                                            className="cursor-default"
                                            onClick={() => openProUpgrade(item.title)}
                                        >
                                            {item.icon && <item.icon />}
                                            <span className="min-w-0 truncate">{item.title}</span>
                                            <ProBadge className="ms-auto shrink-0" />
                                        </SidebarMenuButton>
                                    ) : (
                                        <SidebarMenuButton
                                            tooltip={item.title}
                                            isActive={parentActive && !childActive}
                                            asChild
                                        >
                                            <Link href={item.href} prefetch>
                                                {item.icon && <item.icon />}
                                                <span>{item.title}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    )}
                                    {visibleChildren.length > 0 && sectionActive && (
                                        <SidebarMenuSub>
                                            {visibleChildren.map((child) => (
                                                <SidebarMenuSubItem key={child.title}>
                                                    {child.pro || !child.href ? (
                                                        <SidebarMenuSubButton
                                                            className="cursor-default"
                                                            onClick={() => openProUpgrade(child.title)}
                                                        >
                                                            <span className="min-w-0 truncate">{child.title}</span>
                                                            <ProBadge className="ms-auto shrink-0" />
                                                        </SidebarMenuSubButton>
                                                    ) : (
                                                        <SidebarMenuSubButton
                                                            isActive={isActive(child.href.url)}
                                                            asChild
                                                        >
                                                            <Link href={child.href} prefetch>
                                                                <span>{child.title}</span>
                                                            </Link>
                                                        </SidebarMenuSubButton>
                                                    )}
                                                </SidebarMenuSubItem>
                                            ))}
                                        </SidebarMenuSub>
                                    )}
                                </SidebarMenuItem>
                            );
                        })}
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            {visibleSystemNavItems.length > 0 && (
                <SidebarGroup>
                    <SidebarGroupLabel>{__('System')}</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {visibleSystemNavItems.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    {item.pro || !item.href ? (
                                        <SidebarMenuButton
                                            tooltip={item.title}
                                            className="cursor-default"
                                            onClick={() => openProUpgrade(item.title)}
                                        >
                                            {item.icon && <item.icon />}
                                            <span className="min-w-0 truncate">{item.title}</span>
                                            <ProBadge className="ms-auto shrink-0" />
                                        </SidebarMenuButton>
                                    ) : (
                                        <SidebarMenuButton
                                            asChild
                                            tooltip={item.title}
                                            isActive={isActive(item.href.url)}
                                        >
                                            <Link href={item.href} prefetch>
                                                {item.icon && <item.icon />}
                                                <span>{item.title}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    )}
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            )}
        </>
    );
}
