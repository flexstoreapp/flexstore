import * as StorefrontFooterController from '@/actions/App/Http/Controllers/Admin/StorefrontFooterController';
import * as StorefrontHeaderController from '@/actions/App/Http/Controllers/Admin/StorefrontHeaderController';
import { MenuItemForm } from '@/components/admin/storefront/menu-item-form';
import { __ } from '@/lib/i18n';
import type { MenuItem } from '@/types';

interface MenuItemEditProps {
    menuItem: MenuItem;
    location: 'header' | 'footer';
    [key: string]: unknown;
}

export default function MenuItemEdit({ menuItem, location }: MenuItemEditProps) {
    return <MenuItemForm menuItem={menuItem} location={location} />;
}

MenuItemEdit.layout = ({ location }: MenuItemEditProps) => ({
    title: location === 'footer' ? __('Edit link') : __('Edit menu item'),
    backHref: location === 'footer' ? StorefrontFooterController.edit() : StorefrontHeaderController.edit(),
});
